<?php

use App\Models\AiChatSession;
use App\Models\QuizResult;
use App\Services\AI\AIRecommendationService;
use App\Services\AI\ContextBuilder;
use App\Services\AI\Providers\MockGeminiProvider;
use App\Services\AI\ReActAgentEngine;
use App\Services\AI\ToolRegistry;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->mockProvider = new MockGeminiProvider;
    $this->toolRegistry = new ToolRegistry;
    $this->agentEngine = new ReActAgentEngine($this->toolRegistry);
    $this->contextBuilder = new ContextBuilder;

    $this->service = new AIRecommendationService(
        $this->mockProvider,
        $this->agentEngine,
        $this->contextBuilder
    );
});

describe('AIRecommendationService', function () {
    describe('generateRecommendations', function () {
        test('returns recommendations with profile', function () {
            $context = [
                'user_profile' => [
                    'personality' => 'romantic',
                    'vibe' => 'floral',
                    'budget' => 5000,
                    'style' => 'feminine',
                    'experience' => 'beginner',
                ],
                'available_products' => [],
                'categories' => [],
                'brands' => [],
            ];

            $result = $this->service->generateRecommendations($context);

            expect($result)->toHaveKey('profile');
            expect($result)->toHaveKey('recommendations');
            expect($result['profile'])->toHaveKeys(['type', 'name', 'description']);
        });

        test('caches recommendations', function () {
            $context = [
                'user_profile' => [
                    'personality' => 'cool',
                    'budget' => 8000,
                ],
            ];

            $cacheKey = $this->service->getCacheKey($context);
            Cache::forget($cacheKey);

            $result1 = $this->service->generateRecommendations($context);
            $result2 = $this->service->generateRecommendations($context);

            expect($result1)->toBeArray();
            expect($result2)->toBeArray();
            expect($this->mockProvider->getCallCount())->toBe(1);
        });
    });

    describe('chat', function () {
        test('returns message and optional products', function () {
            $quizResult = QuizResult::create([
                'session_token' => 'test-token-'.uniqid(),
                'answers_json' => ['personality' => 'romantic', 'budget' => 5000],
                'profile_type' => 'fresh_girly',
                'profile_data_json' => ['name' => 'Fresh & Girly'],
                'recommended_product_ids' => [1, 2],
            ]);

            $session = AiChatSession::create([
                'session_token' => 'session-'.uniqid(),
                'quiz_result_id' => $quizResult->id,
            ]);

            $history = collect([]);

            $result = $this->service->chat('おすすめの香水を教えてください', $history, $session);

            expect($result)->toHaveKey('message');
            expect($result['message'])->toBeString();
        });
    });

    describe('filterRecommendations', function () {
        test('filters by price range', function () {
            $recommendations = [
                ['product_id' => 1, 'match_score' => 90, 'price' => 3000],
                ['product_id' => 2, 'match_score' => 85, 'price' => 5000],
                ['product_id' => 3, 'match_score' => 80, 'price' => 8000],
            ];

            $filtered = $this->service->filterRecommendations($recommendations, [
                'max_price' => 5000,
            ]);

            expect($filtered)->toHaveCount(2);
        });

        test('returns all when no filters applied', function () {
            $recommendations = [
                ['product_id' => 1, 'match_score' => 90],
                ['product_id' => 2, 'match_score' => 85],
            ];

            $filtered = $this->service->filterRecommendations($recommendations, []);

            expect($filtered)->toHaveCount(2);
        });
    });

    describe('getCacheKey', function () {
        test('generates consistent cache key', function () {
            $context1 = ['user_profile' => ['personality' => 'romantic']];
            $context2 = ['user_profile' => ['personality' => 'romantic']];

            $key1 = $this->service->getCacheKey($context1);
            $key2 = $this->service->getCacheKey($context2);

            expect($key1)->toBe($key2);
        });

        test('generates different keys for different contexts', function () {
            $context1 = ['user_profile' => ['personality' => 'romantic']];
            $context2 = ['user_profile' => ['personality' => 'cool']];

            $key1 = $this->service->getCacheKey($context1);
            $key2 = $this->service->getCacheKey($context2);

            expect($key1)->not->toBe($key2);
        });
    });
});
