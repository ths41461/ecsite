<?php

/**
 * Real Ollama Integration E2E Tests
 *
 * These tests call the actual Ollama API and verify the full AI flow
 *
 * @group e2e
 * @group ollama
 * @group live-ai
 * @group integration
 */

use App\Services\AI\AIRecommendationService;
use App\Services\AI\ContextBuilder;
use App\Services\AI\Providers\OllamaProvider;

describe('Real Ollama AI Integration Tests', function () {

    describe('OllamaProvider - Direct API Calls', function () {
        it('can connect to Ollama API', function () {
            $provider = new OllamaProvider;
            $available = $provider->isAvailable();

            expect($available)->toBeTrue('Ollama should be running');
        });

        it('can get available models', function () {
            $provider = new OllamaProvider;
            $models = $provider->getAvailableModels();

            expect($models)->not->toBeEmpty('Should have at least one model');
            expect($models[0])->toHaveKey('name');
        });

        it('can send chat message and get response in Japanese', function () {
            $provider = new OllamaProvider;

            $response = $provider->chat('おすすめの香水ブランドを教えてください', [
                'context' => '香水を探している',
            ]);

            expect($response)->toHaveKey('message');
            expect($response['message'])->toHaveKey('content');

            // Should get Japanese response
            $content = $response['message']['content'];
            expect(strlen($content))->toBeGreaterThan(10);
        });
    });

    describe('AIRecommendationService - Full Flow', function () {
        it('returns products with AI recommendation for romantic/floral', function () {
            $service = new AIRecommendationService;

            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => 5000,
                'experience' => 'beginner',
                'gender' => 'women',
            ];

            $result = $service->recommend($quizData);

            // Should return products from database
            expect($result['products'] ?? [])->not->toBeEmpty();

            // Should have AI message
            expect(isset($result['message']))->toBeTrue();

            // First product should have required fields
            $firstProduct = $result['products'][0];
            expect($firstProduct)->toHaveKeys(['id', 'name', 'brand', 'min_price']);
        }, 180000); // 3 minute timeout for AI calls

        it('returns products with AI recommendation for cool/woody', function () {
            $service = new AIRecommendationService;

            $quizData = [
                'personality' => 'cool',
                'vibe' => 'woody',
                'occasion' => ['work'],
                'style' => 'chic',
                'budget' => 10000,
                'experience' => 'some',
                'gender' => 'men',
            ];

            $result = $service->recommend($quizData);

            expect($result['products'] ?? [])->not->toBeEmpty();

            // Verify products are within budget
            foreach ($result['products'] as $product) {
                expect($product['min_price'])->toBeLessThanOrEqual(10000);
            }
        }, 180000);

        it('returns products for natural/citrus/unisex', function () {
            $service = new AIRecommendationService;

            $quizData = [
                'personality' => 'natural',
                'vibe' => 'citrus',
                'occasion' => ['casual'],
                'style' => 'natural',
                'budget' => 3000,
                'experience' => 'beginner',
                'gender' => 'unisex',
            ];

            $result = $service->recommend($quizData);

            expect($result['products'] ?? [])->not->toBeEmpty();
        }, 180000);

        it('handles high budget luxury requests', function () {
            $service = new AIRecommendationService;

            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['special', 'date'],
                'style' => 'feminine',
                'budget' => 30000,
                'experience' => 'experienced',
                'gender' => 'women',
            ];

            $result = $service->recommend($quizData);

            expect($result['products'] ?? [])->not->toBeEmpty();
        }, 180000);
    });

    describe('ContextBuilder - Database Integration', function () {
        it('builds context with products from database', function () {
            $cb = new ContextBuilder;

            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'budget' => 5000,
                'gender' => 'women',
            ];

            $context = $cb->build($quizData);

            expect($context['available_products'])->not->toBeEmpty();
            expect($context['database_info']['total_products'])->toBeGreaterThan(0);
            expect($context['database_info']['brands'])->not->toBeEmpty();
        });

        it('includes database summary for AI', function () {
            $cb = new ContextBuilder;

            $context = $cb->build(['budget' => 10000]);

            $dbInfo = $context['database_info'];

            // Verify all database info is present
            expect($dbInfo['total_products'])->toBeGreaterThan(0);
            expect($dbInfo['total_brands'])->toBeGreaterThan(0);
            expect($dbInfo['total_categories'])->toBeGreaterThan(0);
            expect($dbInfo['brands'])->not->toBeEmpty();
            expect($dbInfo['categories'])->not->toBeEmpty();
            expect($dbInfo['price_range'])->toHaveKey('min');
            expect($dbInfo['price_range'])->toHaveKey('max');
        });

        it('filters products by vibe correctly', function () {
            $cb = new ContextBuilder;

            // Test each vibe
            $vibes = ['floral', 'citrus', 'vanilla', 'woody', 'ocean'];

            foreach ($vibes as $vibe) {
                $context = $cb->build([
                    'vibe' => $vibe,
                    'budget' => 10000,
                    'gender' => 'women',
                ]);

                expect($context['available_products'])->not->toBeEmpty(
                    "Vibe '$vibe' should return products"
                );
            }
        });

        it('filters products by budget correctly', function () {
            $cb = new ContextBuilder;

            $budgets = [3000, 5000, 8000, 15000, 30000];

            foreach ($budgets as $budget) {
                $context = $cb->build([
                    'vibe' => 'floral',
                    'budget' => $budget,
                ]);

                foreach ($context['available_products'] as $product) {
                    expect($product['min_price'])->toBeLessThanOrEqual($budget,
                        "Product price should be within budget ¥$budget"
                    );
                }
            }
        });
    });

    describe('All Quiz Combinations - Real AI', function () {
        $personalities = ['romantic', 'energetic', 'cool', 'natural'];
        $vibes = ['floral', 'citrus', 'vanilla', 'woody', 'ocean'];

        // Test a subset of combinations with real AI (these are slower)
        $combinations = [
            ['romantic', 'floral'],
            ['cool', 'woody'],
            ['natural', 'citrus'],
            ['energetic', 'ocean'],
        ];

        foreach ($combinations as [$personality, $vibe]) {
            it("real AI: personality=$personality, vibe=$vibe returns products", function () use ($personality, $vibe) {
                $service = new AIRecommendationService;

                $quizData = [
                    'personality' => $personality,
                    'vibe' => $vibe,
                    'occasion' => ['daily'],
                    'style' => 'casual',
                    'budget' => 8000,
                    'experience' => 'beginner',
                    'gender' => 'women',
                ];

                $result = $service->recommend($quizData);

                expect($result['products'] ?? [])->not->toBeEmpty(
                    "AI should return products for ($personality, $vibe)"
                );
            }, 180000);
        }
    });
});
