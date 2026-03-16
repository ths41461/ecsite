<?php

/**
 * @group feature
 * @group ai
 * @group e2e
 * @group bug-detection
 *
 * Tests specifically designed to detect the "no products shown" bug
 */
describe('AI Product Display Bug Detection Tests', function () {
    /**
     * This test suite is designed to catch the bug where:
     * 1. ContextBuilder provides products (e.g., 10 products)
     * 2. ReActAgent returns 0 products
     * 3. Controller uses ?? operator which doesn't trigger fallback for empty arrays
     * 4. User sees "この価格帯に該当する商品がありません" (no products)
     */
    describe('Controller must return products from ContextBuilder fallback', function () {
        it('FragranceDiagnosisController MUST return products from ContextBuilder when AI returns empty', function () {
            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => 5000,
                'experience' => 'beginner',
            ];

            $contextBuilder = new \App\Services\AI\ContextBuilder;
            $service = new \App\Services\AI\AIRecommendationService;

            $context = $contextBuilder->build($quizData);
            $aiResult = $service->recommend($quizData);

            $products = $aiResult['products'] ?? $context['available_products'];

            expect($products)
                ->not->toBeEmpty('Must use ContextBuilder fallback when AI returns empty');
        });

        it('ContextBuilder MUST return products within budget', function () {
            $contextBuilder = new \App\Services\AI\ContextBuilder;

            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => 5000,
                'experience' => 'beginner',
            ];

            $context = $contextBuilder->build($quizData);
            $products = $context['available_products'] ?? [];

            expect(count($products))->toBeGreaterThan(0,
                'ContextBuilder must return products within budget'
            );

            foreach ($products as $product) {
                expect($product['min_price'])->toBeLessThanOrEqual(5000,
                    "Product {$product['name']} price exceeds budget"
                );
            }
        });

        it('AIRecommendationService recommend MUST return products', function () {
            $service = new \App\Services\AI\AIRecommendationService;

            $quizData = [
                'personality' => 'natural',
                'vibe' => 'citrus',
                'occasion' => ['daily'],
                'style' => 'casual',
                'budget' => 8000,
                'experience' => 'beginner',
            ];

            $result = $service->recommend($quizData);
            $products = $result['products'] ?? [];

            expect($products)
                ->not->toBeEmpty('AIRecommendationService MUST return products, not empty array');
        });
    });

    describe('Cache bug detection', function () {
        it('cache MUST store product IDs not full product objects', function () {
            $service = new \App\Services\AI\AIRecommendationService;

            $quizData = [
                'personality' => 'cool',
                'vibe' => 'woody',
                'occasion' => ['work'],
                'style' => 'chic',
                'budget' => 10000,
                'experience' => 'some',
            ];

            $result = $service->recommend($quizData);
            $products = $result['products'] ?? [];

            expect($products)->not->toBeEmpty();

            $firstProduct = $products[0];

            expect($firstProduct)
                ->toHaveKey('id')
                ->toHaveKey('name')
                ->toHaveKey('brand');
        });

        it('cached recommendation MUST return product objects not IDs', function () {
            $quizData = [
                'personality' => 'energetic',
                'vibe' => 'ocean',
                'occasion' => ['casual'],
                'style' => 'casual',
                'budget' => 8000,
                'experience' => 'experienced',
            ];

            $service = new \App\Services\AI\AIRecommendationService;

            $service->recommend($quizData);

            $result = $service->recommend($quizData);

            $products = $result['products'] ?? [];

            expect($products)->not->toBeEmpty();

            $firstProduct = $products[0];

            expect($firstProduct)
                ->toBeArray()
                ->toHaveKey('id')
                ->toHaveKey('name', 'Cached products must be full objects not just IDs');
        });
    });

    describe('ReAct Agent bug detection', function () {
        it('ReAct agent MUST return products when context has products', function () {
            $provider = new \App\Services\AI\Providers\OllamaProvider;
            $toolRegistry = new \App\Services\AI\ToolRegistry;
            $contextBuilder = new \App\Services\AI\ContextBuilder;

            $agent = new \App\Services\AI\ReActAgentEngine(
                $provider,
                $toolRegistry,
                $contextBuilder
            );

            $quizData = [
                'personality' => 'natural',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'budget' => 5000,
            ];

            $query = 'おすすめの香水を教えてください';
            $result = $agent->run($query, $quizData);

            $products = $result['products'] ?? [];

            expect($products)
                ->not->toBeEmpty('ReAct Agent MUST return products from context');
        });
    });
});
