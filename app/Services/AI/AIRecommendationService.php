<?php

namespace App\Services\AI;

use App\Models\AiChatSession;
use App\Models\AiMessage;
use App\Models\AIRecommendationCache;
use App\Services\AI\Providers\OllamaProvider;
use Illuminate\Support\Facades\Log;

class AIRecommendationService
{
    private OllamaProvider $provider;

    private ReActAgentEngine $agent;

    private ContextBuilder $contextBuilder;

    private ToolRegistry $toolRegistry;

    public function __construct()
    {
        $this->provider = new OllamaProvider;
        $this->contextBuilder = new ContextBuilder;
        $this->toolRegistry = new ToolRegistry;
        $this->agent = new ReActAgentEngine(
            $this->provider,
            $this->toolRegistry,
            $this->contextBuilder
        );
    }

    /**
     * Get recommendations based on quiz data.
     *
     * @param  array  $quizData  Quiz submission data
     * @return array Recommendations with message, products, and profile
     */
    public function recommend(array $quizData): array
    {
        Log::info('AIRecommendationService@recommend - Starting', $quizData);

        // #region agent log
        try {
            file_put_contents('/code/ecsite/.cursor/debug.log', json_encode([
                'id' => uniqid('log_', true),
                'timestamp' => (int) round(microtime(true) * 1000),
                'runId' => 'pre',
                'hypothesisId' => 'H3',
                'location' => 'AIRecommendationService.php:recommend:start',
                'message' => 'recommend called',
                'data' => [
                    'quiz_keys' => array_keys($quizData),
                    'quiz_gender' => $quizData['gender'] ?? null,
                    'quiz_vibe' => $quizData['vibe'] ?? null,
                    'quiz_budget' => $quizData['budget'] ?? null,
                ],
            ], JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
        } catch (\Throwable $e) {
        }
        // #endregion

        $cacheKey = $this->generateCacheKey($quizData);
        $cached = $this->getCachedRecommendation($cacheKey);

        // #region agent log (sail-safe)
        try {
            $debugPath = base_path('.cursor/debug.log');
            @mkdir(dirname($debugPath), 0777, true);
            file_put_contents($debugPath, json_encode([
                'id' => uniqid('log_', true),
                'timestamp' => (int) round(microtime(true) * 1000),
                'runId' => 'pre',
                'hypothesisId' => 'H4',
                'location' => 'AIRecommendationService.php:recommend:cache_check',
                'message' => 'cache checked',
                'data' => [
                    'cache_hit' => (bool) $cached,
                ],
            ], JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
        } catch (\Throwable $e) {
        }
        // #endregion

        if ($cached) {
            Log::info('AIRecommendationService@recommend - Using cached result');

            // #region agent log
            try {
                file_put_contents('/code/ecsite/.cursor/debug.log', json_encode([
                    'id' => uniqid('log_', true),
                    'timestamp' => (int) round(microtime(true) * 1000),
                    'runId' => 'pre',
                    'hypothesisId' => 'H4',
                    'location' => 'AIRecommendationService.php:recommend:cache_hit',
                    'message' => 'cache hit; agent not executed',
                    'data' => [
                        'cached' => true,
                        'cached_products_type' => gettype($cached['products'] ?? null),
                        'cached_products_count' => is_array($cached['products'] ?? null) ? count($cached['products']) : null,
                    ],
                ], JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
            } catch (\Throwable $e) {
            }
            // #endregion

            return $cached;
        }

        // Get products from database using ContextBuilder
        $context = $this->contextBuilder->build($quizData);
        $availableProducts = $context['available_products'] ?? [];
        $trending = $context['trending_products'] ?? [];

        // Combine products
        $allProducts = [];
        foreach ($availableProducts as $p) {
            $allProducts[$p['id']] = $p;
        }
        foreach ($trending as $p) {
            $allProducts[$p['id']] = $p;
        }
        $products = array_values(array_slice($allProducts, 0, 8));

        // Use REAL AI (single call, not full ReAct) to generate personalized message
        $aiMessage = '香水を選定しました。';

        try {
            // Build a prompt for the AI to generate a personalized message
            $userProfile = $context['user_profile'] ?? [];
            $vibe = $quizData['vibe'] ?? 'floral';
            $gender = $quizData['gender'] ?? 'unisex';
            $budget = $quizData['budget'] ?? 10000;
            $personality = $userProfile['personality'] ?? '';

            $prompt = 'あなたの香水選びをサポートしていました。';
            $prompt .= "\n\n香水の特徴：".$vibe.'系';
            $prompt .= "\n性別：".$gender;
            $prompt .= "\n予算：".$budget.'円';

            if (! empty($personality)) {
                $prompt .= "\n性格タイプ：".$personality;
            }

            $prompt .= "\n\n上記の条件に合わせて、ユーザーに理由を説明してください。2-3文で。";

            // Make a single AI call (much faster than ReAct agent)
            $aiResponse = $this->provider->chat($prompt, $context);

            // Handle both response formats: string or array with content
            $aiMessageContent = $aiResponse['message'] ?? '';
            if (is_array($aiMessageContent)) {
                $aiMessageContent = $aiMessageContent['content'] ?? '';
            }

            if (! empty($aiMessageContent)) {
                $aiMessage = $aiMessageContent;
            }
        } catch (\Throwable $e) {
            Log::warning('AIRecommendationService - AI message generation failed', [
                'error' => $e->getMessage(),
            ]);
        }

        $response = [
            'products' => $products,
            'message' => $aiMessage,
        ];

        // #region agent log (sail-safe)
        try {
            $debugPath = base_path('.cursor/debug.log');
            @mkdir(dirname($debugPath), 0777, true);
            file_put_contents($debugPath, json_encode([
                'id' => uniqid('log_', true),
                'timestamp' => (int) round(microtime(true) * 1000),
                'runId' => 'pre',
                'hypothesisId' => 'H3',
                'location' => 'AIRecommendationService.php:recommend:agent_response_sail',
                'message' => 'agent returned response (sail-safe log)',
                'data' => [
                    'has_error' => isset($response['error']),
                    'products_count' => is_array($response['products'] ?? null) ? count($response['products']) : null,
                    'product_keys_sample' => is_array(($response['products'][0] ?? null)) ? array_keys($response['products'][0]) : null,
                ],
            ], JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
        } catch (\Throwable $e) {
        }
        // #endregion

        // #region agent log
        try {
            file_put_contents('/code/ecsite/.cursor/debug.log', json_encode([
                'id' => uniqid('log_', true),
                'timestamp' => (int) round(microtime(true) * 1000),
                'runId' => 'pre',
                'hypothesisId' => 'H3',
                'location' => 'AIRecommendationService.php:recommend:agent_response',
                'message' => 'agent returned response',
                'data' => [
                    'has_error' => isset($response['error']),
                    'products_count' => is_array($response['products'] ?? null) ? count($response['products']) : null,
                    'product_keys_sample' => is_array(($response['products'][0] ?? null)) ? array_keys($response['products'][0]) : null,
                ],
            ], JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
        } catch (\Throwable $e) {
        }
        // #endregion

        if (! isset($response['error'])) {
            $this->cacheRecommendation($cacheKey, $quizData, $response);
        }

        return $response;
    }

    /**
     * Chat with AI about products.
     *
     * @param  string  $message  User's message
     * @param  string  $sessionId  Chat session ID
     * @return array Response with message and optional products
     */
    public function chat(string $message, string $sessionId): array
    {
        Log::info('AIRecommendationService@chat - Starting', [
            'session_id' => $sessionId,
            'message' => $message,
        ]);

        $session = $this->getOrCreateSession($sessionId);
        $this->saveMessage($session->id, 'user', $message);

        $chatHistory = $this->getChatHistory($session->id);
        $context = $session->context_json ?? [];

        $response = $this->provider->chat($message, $context);

        $this->saveMessage($session->id, 'assistant', $response['message'] ?? '');

        return [
            'message' => $response['message'] ?? '',
            'session_id' => $sessionId,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Build a natural language query from quiz data.
     */
    private function buildQueryFromQuiz(array $quizData): string
    {
        $parts = [];

        if (isset($quizData['personality'])) {
            $parts[] = "性格は{$quizData['personality']}です";
        }

        if (isset($quizData['vibe'])) {
            $parts[] = "好みの香りは{$quizData['vibe']}系です";
        }

        if (isset($quizData['occasion'])) {
            $occasions = is_array($quizData['occasion'])
                ? implode('、', $quizData['occasion'])
                : $quizData['occasion'];
            $parts[] = "使用シーンは{$occasions}です";
        }

        if (isset($quizData['budget'])) {
            $parts[] = "予算は{$quizData['budget']}円くらいです";
        }

        if (empty($parts)) {
            return 'おすすめの香水を教えてください';
        }

        return '私に合う香水を提案してください。'.implode('。', $parts).'。';
    }

    /**
     * Generate cache key from quiz data.
     */
    private function generateCacheKey(array $quizData): string
    {
        ksort($quizData);

        return md5(json_encode($quizData));
    }

    /**
     * Get cached recommendation if available and not expired.
     */
    private function getCachedRecommendation(string $cacheKey): ?array
    {
        $cached = AIRecommendationCache::where('cache_key', $cacheKey)
            ->where('expires_at', '>', now())
            ->first();

        if (! $cached) {
            return null;
        }

        $productIds = $cached->product_ids_json ?? [];

        if (empty($productIds)) {
            return null;
        }

        $products = \App\Models\Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->with(['brand', 'category', 'variants', 'heroImage'])
            ->get()
            ->map(function ($product) {
                $minPriceVariant = $product->variants->where('is_active', true)->first();
                $attributes = $product->attributes_json ?? [];

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'brand' => $product->brand?->name,
                    'category' => $product->category?->name,
                    'min_price' => $minPriceVariant?->price_yen ?? 0,
                    'image_url' => $product->heroImage?->path,
                    'notes' => $attributes['notes'] ?? [],
                    'gender' => $attributes['gender'] ?? 'unisex',
                ];
            })
            ->toArray();

        return [
            'message' => $cached->explanation ?? '',
            'products' => $products,
            'profile' => [],
            'cached' => true,
        ];
    }

    /**
     * Cache the recommendation result.
     */
    private function cacheRecommendation(string $cacheKey, array $quizData, array $response): void
    {
        $ttl = config('ai.cache_ttl_seconds', 3600);

        $products = $response['products'] ?? [];
        $productIds = array_column($products, 'id');

        AIRecommendationCache::updateOrCreate(
            ['cache_key' => $cacheKey],
            [
                'context_hash' => md5(json_encode($quizData)),
                'product_ids_json' => $productIds,
                'explanation' => $response['message'] ?? '',
                'expires_at' => now()->addSeconds($ttl),
            ]
        );
    }

    /**
     * Get or create chat session.
     */
    private function getOrCreateSession(string $sessionId): AiChatSession
    {
        return AiChatSession::firstOrCreate(
            ['session_token' => $sessionId],
            [
                'user_id' => auth()->id(),
                'context_json' => [],
            ]
        );
    }

    /**
     * Save a message to the chat history.
     */
    private function saveMessage(int $sessionId, string $role, string $content): void
    {
        AiMessage::create([
            'session_id' => $sessionId,
            'role' => $role,
            'content' => $content,
            'metadata_json' => [],
        ]);
    }

    /**
     * Get chat history for a session.
     */
    private function getChatHistory(int $sessionId): array
    {
        return AiMessage::where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get(['role', 'content'])
            ->toArray();
    }
}
