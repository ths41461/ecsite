<?php

namespace App\Services\AI;

use App\Services\AI\Providers\OllamaProvider;
use Illuminate\Support\Facades\Log;

class ReActAgentEngine
{
    private OllamaProvider $provider;

    private ToolRegistry $toolRegistry;

    private ContextBuilder $contextBuilder;

    private int $maxIterations = 5;

    private ResponseParser $responseParser;

    public function __construct(
        OllamaProvider $provider,
        ToolRegistry $toolRegistry,
        ContextBuilder $contextBuilder
    ) {
        $this->provider = $provider;
        $this->toolRegistry = $toolRegistry;
        $this->contextBuilder = $contextBuilder;
        $this->responseParser = new ResponseParser;
    }

    /**
     * Set maximum iterations for the ReAct loop.
     */
    public function setMaxIterations(int $iterations): void
    {
        $this->maxIterations = $iterations;
    }

    /**
     * Get maximum iterations.
     */
    public function getMaxIterations(): int
    {
        return $this->maxIterations;
    }

    /**
     * Run the ReAct agent with a user query.
     *
     * @param  string  $query  User's query
     * @param  array  $context  Additional context (budget, preferences, etc.)
     * @return array Response with message, products, and metadata
     */
    public function run(string $query, array $context = []): array
    {
        Log::info('ReActAgentEngine@run - Starting', [
            'query' => $query,
            'context' => $context,
            'max_iterations' => $this->maxIterations,
        ]);

        $fullContext = $this->contextBuilder->build($context);
        $tools = $this->toolRegistry->getTools();
        $conversation = [];
        $allProducts = [];
        $iteration = 0;

        $systemPrompt = $this->buildSystemPrompt($fullContext);

        $conversation[] = ['role' => 'system', 'content' => $systemPrompt];
        $conversation[] = ['role' => 'user', 'content' => $query];

        while ($iteration < $this->maxIterations) {
            $iteration++;

            Log::info("ReActAgentEngine@run - Iteration {$iteration}");

            $response = $this->provider->chatWithTools(
                $this->formatConversation($conversation),
                [],
                $tools
            );

            $assistantMessage = $response['message'] ?? '';
            $toolCalls = $response['tool_calls'] ?? [];

            if (! empty($assistantMessage)) {
                $conversation[] = ['role' => 'assistant', 'content' => $assistantMessage];
            }

            if (empty($toolCalls)) {
                Log::info('ReActAgentEngine@run - No tool calls, returning final response');

                return $this->buildFinalResponse($assistantMessage, $allProducts, $fullContext);
            }

            foreach ($toolCalls as $toolCall) {
                $toolName = $toolCall['function'] ?? '';
                $arguments = $toolCall['arguments'] ?? [];

                Log::info("ReActAgentEngine@run - Executing tool: {$toolName}", $arguments);

                $toolResult = $this->toolRegistry->execute($toolName, $arguments);

                if (isset($toolResult['products'])) {
                    foreach ($toolResult['products'] as $product) {
                        $allProducts[$product['id']] = $product;
                    }
                }

                $toolResultJson = json_encode($toolResult, JSON_UNESCAPED_UNICODE);
                $conversation[] = [
                    'role' => 'user',
                    'content' => "Tool result for {$toolName}: {$toolResultJson}",
                ];
            }
        }

        Log::warning('ReActAgentEngine@run - Max iterations reached');

        return $this->buildFinalResponse(
            '申し訳ございません。処理がタイムアウトしました。もう一度お試しください。',
            $allProducts,
            $fullContext
        );
    }

    /**
     * Build system prompt with context.
     */
    private function buildSystemPrompt(array $context): string
    {
        $prompt = 'あなたは香水専門店のAIアシスタントです。
ユーザーの好みに合わせて最適な香水を提案してください。
日本語で丁寧に回答してください。
利用可能なツールを使って商品を検索してください。

ユーザー情報:
- 予算: '.($context['budget'] ?? 10000).'円
- 性別: '.($context['user_profile']['gender'] ?? 'unisex').'

回答の際は、おすすめする香水の名前、ブランド、価格、香りの特徴（トップノート、ミドルノート、ベースノート）を含めてください。';

        return $prompt;
    }

    /**
     * Format conversation for the provider.
     */
    private function formatConversation(array $conversation): string
    {
        $formatted = '';
        foreach ($conversation as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';
            $formatted .= "[{$role}]: {$content}\n\n";
        }

        return trim($formatted);
    }

    /**
     * Build final response.
     */
    private function buildFinalResponse(string $message, array $products, array $context): array
    {
        return [
            'message' => $message,
            'products' => array_values($products),
            'profile' => $context['user_profile'] ?? [],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
