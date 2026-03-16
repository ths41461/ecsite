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
     * Get maximum iterations for the ReAct loop.
     */
    public function getMaxIterations(): int
    {
        return $this->maxIterations;
    }

    /**
     * Build system prompt with context.
     */
    private function buildSystemPrompt(array $context): string
    {
        $userProfile = $context['user_profile'] ?? [];

        $personalityLabels = [
            'romantic' => 'ロマンチック',
            'energetic' => '元気いっぱい',
            'cool' => 'クール',
            'natural' => 'ナチュラル',
        ];

        $vibeLabels = [
            'floral' => 'フローラル',
            'citrus' => 'シトラス',
            'vanilla' => 'スイート',
            'woody' => 'ウッディ',
            'ocean' => 'オーシャン',
        ];

        $styleLabels = [
            'feminine' => 'フェミニン',
            'casual' => 'カジュアル',
            'chic' => 'シック',
            'natural' => 'ナチュラル',
        ];

        $experienceLabels = [
            'beginner' => '初心者',
            'some' => '少し経験あり',
            'experienced' => '慣れている',
        ];

        $seasonLabels = [
            'spring' => '春夏向け',
            'fall' => '秋冬向け',
            'all' => 'オールシーズン',
        ];

        $genderLabels = [
            'women' => '女性',
            'men' => '男性',
            'unisex' => 'ユニセックス',
        ];

        $concentrationLabels = [
            'parfum' => 'パルファム',
            'edp' => 'オードパルファム',
            'edt' => 'オードトワレ',
            'edc' => 'オードコロン',
            'mist' => 'ボディミスト',
        ];

        $personality = $userProfile['personality'] ?? null;
        $vibe = $userProfile['vibe'] ?? null;
        $style = $userProfile['style'] ?? null;
        $experience = $userProfile['experience'] ?? null;
        $season = $userProfile['season'] ?? null;
        $gender = $userProfile['gender'] ?? 'unisex';

        $prompt = 'あなたは香水専門店のAIアシスタントです。
ユーザーの好みに合わせて最適な香水を提案してください。
日本語で丁寧に回答してください。
利用可能なツールを使って商品を検索してください。

ユーザー情報:
- 性格タイプ: '.($personalityLabels[$personality] ?? '未選択').' ('.($personality ?? 'unknown').')
- 好みの香りのタイプ: '.($vibeLabels[$vibe] ?? '未選択').' ('.($vibe ?? 'unknown').')
- 使用シーン: '.implode('、', $userProfile['occasion'] ?? []).'
- スタイル: '.($styleLabels[$style] ?? '未選択').' ('.($style ?? 'unknown').')
- 経験レベル: '.($experienceLabels[$experience] ?? '未選択').' ('.($experience ?? 'unknown').')
- 季節の好み: '.($seasonLabels[$season] ?? '未選択').' ('.($season ?? 'unknown').')
- 性別: '.($genderLabels[$gender] ?? 'ユニセックス').' ('.$gender.')
- 予算: '.($context['budget'] ?? 10000).'円

、香水を探す際は必ず以下の条件を考慮してください：
1. 予算内の商品のみ提案してください
2. 性の好みに合った商品のみ提案してください（'.($genderLabels[$gender] ?? 'ユニセックス').'向け）
3. 好みの香りのタイプに近いノートを持つ商品を探してください
4. 複数のシーンに対応できる商品をお勧めします

回答する際は、以下の情報を含めてください：
- おすすめする香水の名前とブランド
- 価格
- 香りの特徴（トップノート、ミドルノート、ベースノート）
- なぜこの香水が用户に最適かの理由';

        return $prompt;
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

        // #region agent log
        try {
            file_put_contents('/code/ecsite/.cursor/debug.log', json_encode([
                'id' => uniqid('log_', true),
                'timestamp' => (int) round(microtime(true) * 1000),
                'runId' => 'pre',
                'hypothesisId' => 'H2',
                'location' => 'ReActAgentEngine.php:run:context',
                'message' => 'built fullContext and system prompt',
                'data' => [
                    'context_keys' => array_keys($fullContext),
                    'available_products_count' => is_array($fullContext['available_products'] ?? null) ? count($fullContext['available_products']) : null,
                    'system_prompt_len' => strlen($systemPrompt),
                    'tools_count' => count($tools),
                ],
            ], JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
        } catch (\Throwable $e) {
        }
        // #endregion

        $conversation[] = ['role' => 'system', 'content' => $systemPrompt];
        $conversation[] = ['role' => 'user', 'content' => $query];

        while ($iteration < $this->maxIterations) {
            $iteration++;

            Log::info("ReActAgentEngine@run - Iteration {$iteration}");

            // #region agent log (sail-safe)
            if ($iteration === 1) {
                try {
                    $debugPath = base_path('.cursor/debug.log');
                    @mkdir(dirname($debugPath), 0777, true);
                    file_put_contents($debugPath, json_encode([
                        'id' => uniqid('log_', true),
                        'timestamp' => (int) round(microtime(true) * 1000),
                        'runId' => 'pre',
                        'hypothesisId' => 'H2',
                        'location' => 'ReActAgentEngine.php:run:provider_call',
                        'message' => 'calling chatWithTools (note context passed)',
                        'data' => [
                            'passed_context_keys' => array_keys($fullContext),
                            'full_context_keys' => array_keys($fullContext),
                            'full_context_available_products_count' => is_array($fullContext['available_products'] ?? null) ? count($fullContext['available_products']) : null,
                            'conversation_messages' => count($conversation),
                            'tools_count' => count($tools),
                        ],
                    ], JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
                } catch (\Throwable $e) {
                }
            }
            // #endregion

            $response = $this->provider->chatWithTools(
                $this->formatConversation($conversation),
                $fullContext,
                $tools
            );

            $assistantMessage = $response['message'] ?? '';
            $toolCalls = $response['tool_calls'] ?? [];

            if (! empty($assistantMessage)) {
                $conversation[] = ['role' => 'assistant', 'content' => $assistantMessage];
            }

            if (empty($toolCalls)) {
                Log::info('ReActAgentEngine@run - No tool calls, extracting products from text response');

                // Try to extract product IDs from the text response
                if (! empty($assistantMessage)) {
                    Log::debug('ReActAgentEngine - assistant message length', [
                        'length' => strlen($assistantMessage),
                        'preview' => substr($assistantMessage, 0, 500),
                    ]);

                    $extractedIds = $this->extractProductIdsFromText($assistantMessage);
                    Log::info('ReActAgentEngine@run - Extracted product IDs from text', [
                        'ids' => $extractedIds,
                        'message_preview' => substr($assistantMessage, 0, 200),
                    ]);

                    if (! empty($extractedIds)) {
                        // Execute search with extracted IDs
                        $toolResult = $this->toolRegistry->execute('search_products', [
                            'product_ids' => $extractedIds,
                        ]);

                        Log::info('ReActAgentEngine@run - Tool result for extracted IDs', [
                            'has_products' => isset($toolResult['products']),
                            'count' => $toolResult['products'] ?? [],
                        ]);

                        if (isset($toolResult['products'])) {
                            foreach ($toolResult['products'] as $product) {
                                $allProducts[$product['id']] = $product;
                            }
                        }
                    }
                }

                return $this->buildFinalResponse($assistantMessage, $allProducts, $fullContext);
            }

            foreach ($toolCalls as $toolCall) {
                $toolName = $toolCall['function']['name'] ?? '';
                $arguments = $toolCall['function']['arguments'] ?? [];

                // Handle string arguments (some models pass JSON string)
                if (is_string($arguments)) {
                    $decoded = json_decode($arguments, true);
                    if (is_array($decoded)) {
                        $arguments = $decoded;
                    }
                }

                Log::info("ReActAgentEngine@run - Executing tool: {$toolName}", $arguments);

                $toolResult = $this->toolRegistry->execute($toolName, $arguments);

                if (isset($toolResult['products'])) {
                    foreach ($toolResult['products'] as $product) {
                        $allProducts[$product['id']] = $product;
                    }
                }

                $toolResultJson = json_encode($toolResult, JSON_UNESCAPED_UNICODE);

                // #region agent log
                try {
                    $sample = $toolResult['products'][0] ?? null;
                    file_put_contents('/code/ecsite/.cursor/debug.log', json_encode([
                        'id' => uniqid('log_', true),
                        'timestamp' => (int) round(microtime(true) * 1000),
                        'runId' => 'pre',
                        'hypothesisId' => 'H1',
                        'location' => 'ReActAgentEngine.php:run:tool_result',
                        'message' => 'tool executed; appending result to conversation',
                        'data' => [
                            'tool' => $toolName,
                            'tool_result_keys' => array_keys($toolResult),
                            'products_count' => is_array($toolResult['products'] ?? null) ? count($toolResult['products']) : null,
                            'product_sample_keys' => is_array($sample) ? array_keys($sample) : null,
                            'tool_result_json_len' => is_string($toolResultJson) ? strlen($toolResultJson) : null,
                            'allProducts_count' => count($allProducts),
                        ],
                    ], JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
                } catch (\Throwable $e) {
                }
                // #endregion

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

    /**
     * Extract product IDs from AI text response.
     */
    private function extractProductIdsFromText(string $text): array
    {
        // Match patterns like "ID:34", "商品ID: 34", "ID: 34", "（34）", etc.
        preg_match_all('/(?:ID|商品ID|番号|[-–])\s*[:：]?\s*(\d+)/', $text, $matches);

        $ids = [];
        if (! empty($matches[1])) {
            foreach ($matches[1] as $id) {
                $id = (int) $id;
                // Valid product IDs are typically between 1 and 200
                if ($id > 0 && $id <= 200) {
                    $ids[] = $id;
                }
            }
        }

        // Also try to match product IDs in the format "商品34" or "product 34"
        preg_match_all('/(?:商品|product)\s*(\d+)/', $text, $matches2);
        if (! empty($matches2[1])) {
            foreach ($matches2[1] as $id) {
                $id = (int) $id;
                if ($id > 0 && $id <= 200 && ! in_array($id, $ids)) {
                    $ids[] = $id;
                }
            }
        }

        return array_unique($ids);
    }
}
