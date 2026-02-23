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

        $cacheKey = $this->generateCacheKey($quizData);
        $cached = $this->getCachedRecommendation($cacheKey);

        if ($cached) {
            Log::info('AIRecommendationService@recommend - Using cached result');

            return $cached;
        }

        $query = $this->buildQueryFromQuiz($quizData);
        $response = $this->agent->run($query, $quizData);

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

        return [
            'message' => $cached->explanation ?? '',
            'products' => $cached->product_ids_json ?? [],
            'cached' => true,
        ];
    }

    /**
     * Cache the recommendation result.
     */
    private function cacheRecommendation(string $cacheKey, array $quizData, array $response): void
    {
        $ttl = config('ai.cache_ttl_seconds', 3600);

        AIRecommendationCache::updateOrCreate(
            ['cache_key' => $cacheKey],
            [
                'context_hash' => md5(json_encode($quizData)),
                'product_ids_json' => $response['products'] ?? [],
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
