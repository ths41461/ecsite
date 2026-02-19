<?php

namespace App\Services\AI\Providers;

use App\Services\AI\ResponseParser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements AIProviderInterface
{
    protected string $apiKey;

    protected string $baseUrl;

    protected string $primaryModel;

    protected string $fallbackModel;

    protected ResponseParser $parser;

    public function __construct(?ResponseParser $parser = null)
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $this->primaryModel = config('services.gemini.primary_model', 'gemini-2.5-flash-lite');
        $this->fallbackModel = config('services.gemini.fallback_model', 'gemini-2.5-flash');
        $this->parser = $parser ?? new ResponseParser;
    }

    public function generateWithTools(array $conversation, array $tools): array
    {
        $contents = $this->buildConversationContents($conversation);
        $systemPrompt = $this->extractSystemPrompt($conversation);
        $formattedTools = $this->formatToolsForGemini($tools);

        $payload = [
            'contents' => $contents,
            'tools' => $formattedTools,
        ];

        if ($systemPrompt) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemPrompt]],
            ];
        }

        return $this->sendRequest($payload, false);
    }

    public function generate(string $systemPrompt, string $userMessage, array $tools = [], bool $useFallback = false): array
    {
        $payload = $this->buildRequestPayload($systemPrompt, $userMessage, $tools);

        return $this->sendRequest($payload, $useFallback);
    }

    public function chat(string $message, array $context, bool $useFallback = false): array
    {
        $systemPrompt = $this->buildChatSystemPrompt($context);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $message]],
                ],
            ],
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
            ],
        ];

        $response = $this->sendRequest($payload, $useFallback);

        return [
            'message' => $response['content'] ?? '',
            'products' => $response['products'] ?? [],
        ];
    }

    protected function sendRequest(array $payload, bool $useFallback): array
    {
        $url = $this->getApiUrl($useFallback);

        try {
            $response = Http::timeout(60)
                ->post($url, $payload);

            if ($response->failed()) {
                $error = $this->parser->parseError($response->json() ?? []);

                Log::warning('Gemini API error', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                if ($error['is_rate_limit'] && ! $useFallback) {
                    Log::info('Retrying with fallback model');

                    return $this->sendRequest($payload, true);
                }

                throw new \RuntimeException('Gemini API error: '.$error['message']);
            }

            $data = $response->json();
            $parsed = $this->parser->parseGeminiResponse($data);

            if ($parsed['type'] === 'function_call') {
                return [
                    'type' => 'function_call',
                    'function_name' => $parsed['function_name'],
                    'function_args' => $parsed['function_args'],
                ];
            }

            return [
                'type' => 'text',
                'content' => $parsed['content'] ?? '',
            ];
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Gemini API exception', [
                'message' => $e->getMessage(),
                'use_fallback' => $useFallback,
            ]);

            if (! $useFallback) {
                Log::info('Retrying with fallback model after exception');

                return $this->sendRequest($payload, true);
            }

            throw $e;
        }
    }

    public function buildRequestPayload(
        string $systemPrompt,
        string $userMessage,
        array $tools = [],
        bool $jsonResponse = false
    ): array {
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $userMessage]],
                ],
            ],
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
        ];

        if (! empty($tools)) {
            $payload['tools'] = $this->formatToolsForGemini($tools);
        }

        if ($jsonResponse) {
            $payload['generationConfig'] = [
                'responseMimeType' => 'application/json',
            ];
        }

        return $payload;
    }

    public function buildConversationContents(array $conversation): array
    {
        $contents = [];

        foreach ($conversation as $message) {
            $role = $message['role'] ?? 'user';

            if ($role === 'system') {
                continue;
            }

            if ($role === 'tool') {
                $contents[] = [
                    'role' => 'function',
                    'parts' => [
                        [
                            'functionResponse' => [
                                'name' => $message['tool_name'] ?? 'unknown',
                                'response' => json_decode($message['content'], true) ?? [],
                            ],
                        ],
                    ],
                ];

                continue;
            }

            if (isset($message['tool_calls'])) {
                $parts = [];
                foreach ($message['tool_calls'] as $toolCall) {
                    $parts[] = [
                        'functionCall' => [
                            'name' => $toolCall['function']['name'] ?? '',
                            'args' => json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [],
                        ],
                    ];
                }
                $contents[] = [
                    'role' => 'model',
                    'parts' => $parts,
                ];

                continue;
            }

            $geminiRole = $role === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $geminiRole,
                'parts' => [['text' => $message['content'] ?? '']],
            ];
        }

        return $contents;
    }

    public function getApiUrl(bool $useFallback = false): string
    {
        $model = $useFallback ? $this->fallbackModel : $this->primaryModel;

        return "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";
    }

    public function formatToolsForGemini(array $tools): array
    {
        if (empty($tools)) {
            return [];
        }

        $declarations = [];
        foreach ($tools as $tool) {
            if (isset($tool['function'])) {
                $declarations[] = [
                    'name' => $tool['function']['name'],
                    'description' => $tool['function']['description'] ?? '',
                    'parameters' => $tool['function']['parameters'] ?? ['type' => 'object'],
                ];
            }
        }

        return [['functionDeclarations' => $declarations]];
    }

    protected function extractSystemPrompt(array $conversation): ?string
    {
        foreach ($conversation as $message) {
            if (($message['role'] ?? '') === 'system') {
                return $message['content'] ?? null;
            }
        }

        return null;
    }

    protected function buildChatSystemPrompt(array $context): string
    {
        $profileType = $context['profile_type'] ?? 'unknown';
        $budget = $context['budget'] ?? 5000;
        $quizContextJson = json_encode($context['quiz_context'] ?? [], JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are a helpful, friendly fragrance consultant chatting with a young Japanese woman about perfumes. 
Be encouraging, use casual but polite Japanese, and explain fragrance concepts in simple terms.

CONTEXT:
- User's Scent Profile: {$profileType}
- Budget: Under ¥{$budget}
- Quiz Answers: {$quizContextJson}

Be helpful, answer questions clearly, and suggest products from the catalog when appropriate. 
If you mention specific products, include their IDs so we can display product cards.
Respond in Japanese.
PROMPT;
    }
}
