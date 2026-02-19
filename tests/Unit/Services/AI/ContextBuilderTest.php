<?php

use App\Services\AI\ContextBuilder;

beforeEach(function () {
    $this->builder = new ContextBuilder;
});

describe('ContextBuilder', function () {
    describe('build', function () {
        test('returns user profile structure', function () {
            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => 5000,
                'experience' => 'beginner',
                'season' => 'spring',
            ];

            $context = $this->builder->build($quizData);

            expect($context)->toHaveKey('user_profile');
            expect($context['user_profile'])->toHaveKeys([
                'personality',
                'vibe',
                'occasion',
                'style',
                'budget',
                'experience',
                'season',
            ]);
            expect($context['user_profile']['personality'])->toBe('romantic');
            expect($context['user_profile']['budget'])->toBe(5000);
        });

        test('returns available products under budget', function () {
            $quizData = [
                'budget' => 3000,
                'personality' => 'romantic',
            ];

            $context = $this->builder->build($quizData);

            expect($context)->toHaveKey('available_products');
            expect($context['available_products'])->toBeArray();

            if (count($context['available_products']) > 0) {
                $product = $context['available_products'][0];
                expect($product)->toHaveKeys(['id', 'name', 'brand', 'category', 'notes', 'min_price', 'max_price']);
                expect($product['min_price'])->toBeLessThanOrEqual(3000);
            }
        });

        test('returns categories list', function () {
            $context = $this->builder->build(['budget' => 5000]);

            expect($context)->toHaveKey('categories');
            expect($context['categories'])->toBeArray();

            if (count($context['categories']) > 0) {
                expect($context['categories'][0])->toHaveKeys(['id', 'name']);
            }
        });

        test('returns brands list', function () {
            $context = $this->builder->build(['budget' => 5000]);

            expect($context)->toHaveKey('brands');
            expect($context['brands'])->toBeArray();

            if (count($context['brands']) > 0) {
                expect($context['brands'][0])->toHaveKeys(['id', 'name']);
            }
        });

        test('handles missing optional fields', function () {
            $quizData = [
                'budget' => 5000,
                'personality' => 'romantic',
            ];

            $context = $this->builder->build($quizData);

            expect($context)->toBeArray();
            expect($context['user_profile']['season'])->toBeNull();
        });
    });

    describe('buildForChat', function () {
        test('returns chat context with quiz data', function () {
            $quizResult = \App\Models\QuizResult::create([
                'session_token' => 'test-token-'.uniqid(),
                'answers_json' => [
                    'personality' => 'romantic',
                    'budget' => 5000,
                ],
                'profile_type' => 'fresh_girly',
                'profile_data_json' => ['name' => 'Fresh & Girly'],
                'recommended_product_ids' => [1, 2, 3],
            ]);

            $session = \App\Models\AiChatSession::create([
                'session_token' => 'session-'.uniqid(),
                'quiz_result_id' => $quizResult->id,
            ]);

            $history = collect([
                new \App\Models\AiMessage([
                    'role' => 'user',
                    'content' => 'Hello',
                ]),
            ]);

            $context = $this->builder->buildForChat($session, $history);

            expect($context)->toHaveKey('quiz_context');
            expect($context)->toHaveKey('profile_type');
            expect($context)->toHaveKey('previous_recommendations');
            expect($context)->toHaveKey('budget');
            expect($context['profile_type'])->toBe('fresh_girly');
            expect($context['budget'])->toBe(5000);
        });
    });
});
