<?php

namespace App\Services\AI\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaProvider
{
    private string $host;

    private string $model;

    private int $timeout;

    private int $keepAlive;

    private array $options;

    private array $fallbackModels;

    public function __construct(?string $host = null, ?string $model = null)
    {
        $this->host = $host ?? config('ai.ollama.host', 'http://ollama:11434');
        $this->model = $model ?? config('ai.ollama.model', 'qwen3:8b');
        $this->timeout = config('ai.ollama.timeout', 300);
        $this->keepAlive = config('ai.ollama.keep_alive', -1);
        $this->options = config('ai.ollama.options', [
            'temperature' => 0.1,
            'num_predict' => 512,
            'top_p' => 0.9,
        ]);
        $this->fallbackModels = config('ai.ollama.fallback_models', ['gemma3:latest']);
    }

    /**
     * Send a chat message to Ollama.
     */
    public function chat(string $message, array $context = []): array
    {
        Log::info('OllamaProvider@chat', [
            'message' => $message,
            'context_keys' => array_keys($context),
            'model' => $this->model,
        ]);

        $systemPrompt = $this->buildSystemPrompt($context);

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ],
            'stream' => false,
            'keep_alive' => $this->keepAlive,
            'options' => $this->options,
        ];

        return $this->sendRequest('/api/chat', $payload);
    }

    /**
     * Send a chat message with tool definitions.
     * Falls back to non-tool chat if model doesn't support tools.
     */
    public function chatWithTools(string $message, array $context = [], array $tools = []): array
    {
        Log::info('OllamaProvider@chatWithTools', [
            'message' => $message,
            'tools_count' => count($tools),
            'model' => $this->model,
        ]);

        $systemPrompt = $this->buildSystemPrompt($context);

        // #region agent log
        try {
            file_put_contents('/code/ecsite/.cursor/debug.log', json_encode([
                'id' => uniqid('log_', true),
                'timestamp' => (int) round(microtime(true) * 1000),
                'runId' => 'pre',
                'hypothesisId' => 'H2',
                'location' => 'OllamaProvider.php:chatWithTools:payload',
                'message' => 'building ollama payload for tools chat',
                'data' => [
                    'context_keys' => array_keys($context),
                    'context_available_products_count' => is_array($context['available_products'] ?? null) ? count($context['available_products']) : null,
                    'system_prompt_len' => strlen($systemPrompt),
                    'message_len' => strlen($message),
                    'tools_count' => count($tools),
                ],
            ], JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
        } catch (\Throwable $e) {
        }
        // #endregion

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ],
            'stream' => false,
            'tools' => $tools,
            'keep_alive' => $this->keepAlive,
            'options' => array_merge($this->options, ['temperature' => 0.1]),
        ];

        Log::debug('OllamaProvider chatWithTools payload', [
            'has_tools' => ! empty($tools),
            'tools_count' => count($tools),
            'tools_first_keys' => ! empty($tools) ? array_keys($tools[0] ?? []) : [],
        ]);

        $response = $this->sendRequest('/api/chat', $payload);

        // Tool calls are now returned in the root of the response by sendRequest
        $toolCalls = $response['tool_calls'] ?? [];

        return [
            'message' => $response['message'] ?? '',
            'tool_calls' => $toolCalls,
            'model' => $response['model'] ?? $this->model,
            'raw' => $response['raw'] ?? null,
        ];
    }

    /**
     * Get the current model.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Set the model to use.
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * Get fallback models.
     */
    public function getFallbackModels(): array
    {
        return $this->fallbackModels;
    }

    /**
     * Set fallback models.
     */
    public function setFallbackModels(array $models): void
    {
        $this->fallbackModels = $models;
    }

    /**
     * Get available models from Ollama.
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::timeout(10)->get($this->host.'/api/tags');

            Log::info('OllamaProvider@getAvailableModels', [
                'host' => $this->host,
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if (! $response->successful()) {
                Log::warning('OllamaProvider@getAvailableModels - Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();
            $models = [];

            foreach ($data['models'] ?? [] as $model) {
                $name = $model['name'] ?? '';
                if (str_contains($name, ':')) {
                    $name = explode(':', $name)[0];
                }
                if (! in_array($name, $models)) {
                    $models[] = $name;
                }
            }

            Log::info('OllamaProvider@getAvailableModels - Found models', [
                'models' => $models,
            ]);

            return $models;
        } catch (\Exception $e) {
            Log::error('OllamaProvider@getAvailableModels - Exception', [
                'error' => $e->getMessage(),
                'host' => $this->host,
            ]);

            return [];
        }
    }

    /**
     * Chat with automatic model fallback.
     */
    public function chatWithFallback(string $message, array $context = [], ?array $fallbackModels = null): array
    {
        $modelsToTry = [$this->model];
        $fallbacks = $fallbackModels ?? $this->fallbackModels;

        foreach ($fallbacks as $fallback) {
            if (! in_array($fallback, $modelsToTry)) {
                $modelsToTry[] = $fallback;
            }
        }

        $lastError = null;

        foreach ($modelsToTry as $model) {
            $originalModel = $this->model;
            $this->model = $model;

            $response = $this->chat($message, $context);

            if (! isset($response['error'])) {
                $response['model_used'] = $model;
                $this->model = $originalModel;

                return $response;
            }

            $lastError = $response['error'];
            Log::warning('OllamaProvider@chatWithFallback - Model failed, trying next', [
                'model' => $model,
                'error' => $lastError,
            ]);
        }

        return [
            'error' => $lastError ?? 'All models failed',
            'message' => '',
            'model_used' => null,
        ];
    }

    /**
     * Check if Ollama is available.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->host.'/api/tags');

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('OllamaProvider@isAvailable - Connection failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send HTTP request to Ollama.
     */
    private function sendRequest(string $endpoint, array $payload): array
    {
        try {
            Log::debug('OllamaProvider sendRequest called', [
                'endpoint' => $endpoint,
                'has_tools' => isset($payload['tools']),
                'tools_count' => isset($payload['tools']) ? count($payload['tools']) : 0,
            ]);

            $response = Http::timeout($this->timeout)
                ->post($this->host.$endpoint, $payload);

            if (! $response->successful()) {
                Log::error('OllamaProvider - API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'error' => "Ollama API error: {$response->status()}",
                    'message' => '',
                    'model' => $this->model,
                ];
            }

            $data = $response->json();

            // Debug: log raw response structure
            Log::debug('OllamaProvider sendRequest response keys', array_keys($data));
            if (isset($data['message'])) {
                Log::debug('OllamaProvider message keys', array_keys($data['message']));
                if (isset($data['message']['tool_calls'])) {
                    Log::debug('OllamaProvider tool_calls count', [count($data['message']['tool_calls'])]);
                }
            }

            // Extract tool calls if present
            $toolCalls = [];
            if (isset($data['message']['tool_calls'])) {
                Log::info('OllamaProvider - Found tool calls in response', [
                    'count' => count($data['message']['tool_calls']),
                ]);
                foreach ($data['message']['tool_calls'] as $toolCall) {
                    $arguments = $toolCall['function']['arguments'] ?? [];
                    // Handle if arguments is a string (JSON)
                    if (is_string($arguments)) {
                        $decoded = json_decode($arguments, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $arguments = $decoded;
                        }
                    }
                    $toolCalls[] = [
                        'function' => [
                            'name' => $toolCall['function']['name'] ?? '',
                            'arguments' => $arguments,
                        ],
                    ];
                }
            }

            $result = [
                'message' => $data['message']['content'] ?? '',
                'model' => $data['model'] ?? $this->model,
                'done' => $data['done'] ?? true,
                'raw' => $data,
                'tool_calls' => $toolCalls,
            ];

            Log::debug('OllamaProvider returning tool_calls count', [count($toolCalls)]);

            return $result;
        } catch (\Exception $e) {
            Log::error('OllamaProvider - Connection error', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
            ]);

            return [
                'error' => $e->getMessage(),
                'message' => '',
                'model' => $this->model,
            ];
        }
    }

    /**
     * Build system prompt from context.
     */
    private function buildSystemPrompt(array $context): string
    {
        $prompt = 'あなたは香水の専門家アシスタントです。日本語で丁寧に回答してください。';
        $prompt .= ' 以下の商品リストからだけを提案してください。';
        $prompt .= ' 実際の商品を必ず含めて、架空の商品を提案しないでください。';
        $prompt .= ' 必ず提供された商品ID(ID数字)を含めて提案してください。';

        if (! empty($context['available_products'])) {
            $prompt .= "\n\n利用可能な商品リスト:\n";
            foreach ($context['available_products'] as $product) {
                $notes = implode(', ', $product['notes'] ?? []);
                $prompt .= "- ID:{$product['id']} | {$product['brand']} | {$product['name']} | ¥{$product['min_price']} | ノート: {$notes}\n";
            }
        }

        if (! empty($context['trending_products'])) {
            $prompt .= "\nトレンド商品:\n";
            foreach ($context['trending_products'] as $product) {
                $notes = implode(', ', $product['notes'] ?? []);
                $prompt .= "- ID:{$product['id']} | {$product['brand']} | {$product['name']} | ¥{$product['min_price']}\n";
            }
        }

        if (! empty($context['user_profile'])) {
            $prompt .= "\n\nユーザー趣向:\n";
            foreach ($context['user_profile'] as $key => $value) {
                if (is_array($value)) {
                    // Handle nested arrays (like personality_details, vibe_details)
                    $value = $this->formatArrayValue($value);
                }
                if ($value !== null && $value !== '') {
                    $prompt .= "- {$key}: {$value}\n";
                }
            }
        }

        return $prompt;
    }

    /**
     * Format array value for prompt.
     */
    private function formatArrayValue(array $value): string
    {
        $parts = [];
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                // Recursively handle nested arrays
                $nested = [];
                foreach ($v as $nk => $nv) {
                    if (is_array($nv)) {
                        $nested[] = $nk.':'.json_encode($nv);
                    } else {
                        $nested[] = $nk.':'.$nv;
                    }
                }
                $parts[] = $k.':['.implode(', ', $nested).']';
            } else {
                $parts[] = $k.':'.$v;
            }
        }

        return implode(', ', $parts);
    }
}
