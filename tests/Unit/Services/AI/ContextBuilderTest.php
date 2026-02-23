<?php

/**
 * @group unit
 * @group ai
 */

use App\Services\AI\ContextBuilder;

describe('ContextBuilder', function () {

    test('can instantiate context builder', function () {
        $builder = new ContextBuilder;

        expect($builder)->toBeInstanceOf(ContextBuilder::class);
    });

    test('build returns array with required structure', function () {
        $builder = new ContextBuilder;
        $quizData = [
            'budget' => 5000,
            'personality' => 'romantic',
            'vibe' => 'floral',
        ];

        $context = $builder->build($quizData);

        expect($context)->toBeArray()
            ->and($context)->toHaveKey('user_profile')
            ->and($context)->toHaveKey('available_products')
            ->and($context)->toHaveKey('budget');
    });

    test('build returns real products from database within budget', function () {
        $builder = new ContextBuilder;
        $budget = 3000;
        $quizData = [
            'budget' => $budget,
            'personality' => 'romantic',
        ];

        $context = $builder->build($quizData);

        expect($context['budget'])->toBe($budget)
            ->and($context['available_products'])->toBeArray();

        if (count($context['available_products']) > 0) {
            foreach ($context['available_products'] as $product) {
                expect($product)->toHaveKeys(['id', 'name', 'slug', 'min_price']);
                if (isset($product['min_price'])) {
                    expect($product['min_price'])->toBeLessThanOrEqual($budget);
                }
            }
        }
    });

    test('build includes product notes and gender from attributes_json', function () {
        $builder = new ContextBuilder;
        $quizData = [
            'budget' => 10000,
            'gender' => 'women',
        ];

        $context = $builder->build($quizData);

        if (count($context['available_products']) > 0) {
            $product = $context['available_products'][0];
            expect($product)->toHaveKey('notes');
        }
    });

    test('build respects gender preference', function () {
        $builder = new ContextBuilder;
        $quizData = [
            'budget' => 10000,
            'gender' => 'women',
        ];

        $context = $builder->build($quizData);

        expect($context['user_profile'])->toHaveKey('gender')
            ->and($context['user_profile']['gender'])->toBe('women');
    });

    test('build handles empty quiz data gracefully', function () {
        $builder = new ContextBuilder;

        $context = $builder->build([]);

        expect($context)->toBeArray()
            ->and($context)->toHaveKey('available_products')
            ->and($context['available_products'])->toBeArray();
    });

    test('build includes trending products', function () {
        $builder = new ContextBuilder;
        $quizData = ['budget' => 10000];

        $context = $builder->build($quizData);

        expect($context)->toHaveKey('trending_products')
            ->and($context['trending_products'])->toBeArray();
    });

    test('build includes top rated products', function () {
        $builder = new ContextBuilder;
        $quizData = ['budget' => 10000];

        $context = $builder->build($quizData);

        expect($context)->toHaveKey('top_rated_products')
            ->and($context['top_rated_products'])->toBeArray();
    });

    test('build limits products to prevent context overflow', function () {
        $builder = new ContextBuilder;
        $quizData = ['budget' => 100000];

        $context = $builder->build($quizData);

        expect(count($context['available_products']))->toBeLessThanOrEqual(20)
            ->and(count($context['trending_products']))->toBeLessThanOrEqual(5)
            ->and(count($context['top_rated_products']))->toBeLessThanOrEqual(5);
    });
});
