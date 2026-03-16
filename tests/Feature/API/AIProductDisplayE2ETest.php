<?php

/**
 * @group feature
 * @group ai
 * @group e2e
 * @group live-api
 */

use App\Models\Product;

describe('AI Recommendation E2E Tests - Product Display Issue', function () {
    /**
     * These tests verify that the AI recommendation system actually returns products
     * and does not show "no products in price range" errors.
     */
    describe('Quiz Submission Returns Products', function () {
        it('MUST return at least 1 product for budget ¥3,000', function () {
            $response = $this->postJson('/api/v1/ai/quiz', [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => 3000,
                'experience' => 'beginner',
            ]);

            $response->assertStatus(200);

            $recommendations = $response->json('data.recommendations');

            expect($recommendations)
                ->toBeArray()
                ->not->toBeEmpty('AI quiz MUST return at least 1 product for ¥3,000 budget - currently returning empty');

            expect($recommendations[0])->toHaveKeys(['id', 'name', 'brand', 'price']);
        });

        it('MUST return at least 1 product for budget ¥5,000', function () {
            $response = $this->postJson('/api/v1/ai/quiz', [
                'personality' => 'cool',
                'vibe' => 'woody',
                'occasion' => ['work'],
                'style' => 'chic',
                'budget' => 5000,
                'experience' => 'intermediate',
            ]);

            $response->assertStatus(200);

            $recommendations = $response->json('data.recommendations');

            expect($recommendations)
                ->toBeArray()
                ->not->toBeEmpty('AI quiz MUST return at least 1 product for ¥5,000 budget');

            expect($recommendations[0])->toHaveKeys(['id', 'name', 'brand', 'price']);
        });

        it('MUST return at least 1 product for budget ¥8,000', function () {
            $response = $this->postJson('/api/v1/ai/quiz', [
                'personality' => 'energetic',
                'vibe' => 'citrus',
                'occasion' => ['casual'],
                'style' => 'casual',
                'budget' => 8000,
                'experience' => 'advanced',
            ]);

            $response->assertStatus(200);

            $recommendations = $response->json('data.recommendations');

            expect($recommendations)
                ->toBeArray()
                ->not->toBeEmpty('AI quiz MUST return at least 1 product for ¥8,000 budget');

            expect($recommendations[0])->toHaveKeys(['id', 'name', 'brand', 'price']);
        });

        it('MUST return at least 1 product for budget ¥10,000+', function () {
            $response = $this->postJson('/api/v1/ai/quiz', [
                'personality' => 'natural',
                'vibe' => 'ocean',
                'occasion' => ['date', 'special'],
                'style' => 'casual',
                'budget' => 15000,
                'experience' => 'beginner',
            ]);

            $response->assertStatus(200);

            $recommendations = $response->json('data.recommendations');

            expect($recommendations)
                ->toBeArray()
                ->not->toBeEmpty('AI quiz MUST return at least 1 product for ¥15,000 budget');

            expect($recommendations[0])->toHaveKeys(['id', 'name', 'brand', 'price']);
        });
    });

    describe('Product Price Within Budget', function () {
        it('ALL returned products must be within the requested budget', function () {
            $budget = 5000;

            $response = $this->postJson('/api/v1/ai/quiz', [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => $budget,
                'experience' => 'beginner',
            ]);

            $response->assertStatus(200);

            $recommendations = $response->json('data.recommendations');

            expect($recommendations)->not->toBeEmpty();

            foreach ($recommendations as $product) {
                expect($product['price'])
                    ->toBeLessThanOrEqual($budget,
                        "Product {$product['name']} price ({$product['price']}) exceeds budget ({$budget})"
                    );
            }
        });

        it('low budget ¥3,000 returns affordable products', function () {
            $budget = 3000;

            $response = $this->postJson('/api/v1/ai/quiz', [
                'personality' => 'natural',
                'vibe' => 'citrus',
                'occasion' => ['daily'],
                'style' => 'casual',
                'budget' => $budget,
                'experience' => 'beginner',
            ]);

            $response->assertStatus(200);

            $recommendations = $response->json('data.recommendations');

            expect($recommendations)->not->toBeEmpty();

            foreach ($recommendations as $product) {
                expect($product['price'])
                    ->toBeLessThanOrEqual($budget,
                        "Product price exceeds budget: {$product['price']} > {$budget}"
                    );
            }
        });
    });

    describe('Database Has Products', function () {
        it('database MUST have active products with variants under ¥5,000', function () {
            $products = Product::where('is_active', true)
                ->whereHas('variants', function ($q) {
                    $q->where('is_active', true)->where('price_yen', '<=', 5000);
                })
                ->count();

            expect($products)
                ->toBeGreaterThan(0,
                    'Database must have products under ¥5,000 for testing - '.
                    'check database seeding'
                );
        });

        it('database MUST have active products with variants under ¥3,000', function () {
            $products = Product::where('is_active', true)
                ->whereHas('variants', function ($q) {
                    $q->where('is_active', true)->where('price_yen', '<=', 3000);
                })
                ->count();

            expect($products)
                ->toBeGreaterThan(0,
                    'Database must have products under ¥3,000 - check database seeding'
                );
        });
    });

    describe('Session-based Recommendation Retrieval', function () {
        it('retrieving recommendations by session_id MUST return products', function () {
            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => 5000,
                'experience' => 'beginner',
            ];

            $submitResponse = $this->postJson('/api/v1/ai/quiz', $quizData);

            $submitResponse->assertStatus(200);

            $sessionId = $submitResponse->json('data.session_id');

            expect($sessionId)->not->toBeNull();

            $recommendationsResponse = $this->getJson('/api/v1/ai/recommendations?session_id='.$sessionId);

            $recommendationsResponse->assertStatus(200);

            $recommendations = $recommendationsResponse->json('data.recommendations');

            expect($recommendations)
                ->toBeArray()
                ->not->toBeEmpty('Recommendations retrieved by session_id MUST not be empty');
        });
    });

    describe('Quiz Result Persistence', function () {
        it('quiz result MUST store recommended product IDs', function () {
            $response = $this->postJson('/api/v1/ai/quiz', [
                'personality' => 'cool',
                'vibe' => 'ocean',
                'occasion' => ['work'],
                'style' => 'chic',
                'budget' => 8000,
                'experience' => 'advanced',
            ]);

            $response->assertStatus(200);

            $sessionId = $response->json('data.session_id');

            $session = \App\Models\AiChatSession::where('session_token', $sessionId)
                ->with('quizResult')
                ->first();

            expect($session)->not->toBeNull();
            expect($session->quizResult)->not->toBeNull();
            expect($session->quizResult->recommended_product_ids)
                ->not->toBeEmpty('recommended_product_ids must be stored in database');
        });
    });

    describe('Profile Generation', function () {
        it('response MUST include scent profile data', function () {
            $response = $this->postJson('/api/v1/ai/quiz', [
                'personality' => 'romantic',
                'vibe' => 'vanilla',
                'occasion' => ['date'],
                'style' => 'feminine',
                'budget' => 5000,
                'experience' => 'beginner',
            ]);

            $response->assertStatus(200);

            $profile = $response->json('data.profile');

            expect($profile)->not->toBeEmpty();
            expect($profile)->toHaveKeys(['type', 'name', 'description']);
        });
    });

    describe('All Vibe Types Return Products', function () {
        $vibes = ['floral', 'citrus', 'vanilla', 'woody', 'ocean'];

        foreach ($vibes as $vibe) {
            it("vibe '$vibe' MUST return at least 1 product", function () use ($vibe) {
                $response = $this->postJson('/api/v1/ai/quiz', [
                    'personality' => 'natural',
                    'vibe' => $vibe,
                    'occasion' => ['daily'],
                    'style' => 'casual',
                    'budget' => 8000,
                    'experience' => 'beginner',
                ]);

                $response->assertStatus(200);

                $recommendations = $response->json('data.recommendations');

                expect($recommendations)
                    ->not->toBeEmpty("AI MUST return products for vibe: $vibe");
            });
        }
    });

    describe('All Personality Types Return Products', function () {
        $personalities = ['romantic', 'energetic', 'cool', 'natural'];

        foreach ($personalities as $personality) {
            it("personality '$personality' MUST return at least 1 product", function () use ($personality) {
                $response = $this->postJson('/api/v1/ai/quiz', [
                    'personality' => $personality,
                    'vibe' => 'floral',
                    'occasion' => ['daily'],
                    'style' => 'casual',
                    'budget' => 8000,
                    'experience' => 'beginner',
                ]);

                $response->assertStatus(200);

                $recommendations = $response->json('data.recommendations');

                expect($recommendations)
                    ->not->toBeEmpty("AI MUST return products for personality: $personality");
            });
        }
    });
});
