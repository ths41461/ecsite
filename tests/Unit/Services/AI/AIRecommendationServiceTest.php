<?php

/**
 * @group unit
 * @group ai
 */

use App\Services\AI\AIRecommendationService;

describe('AIRecommendationService', function () {

    test('can instantiate service', function () {
        $service = new AIRecommendationService;

        expect($service)->toBeInstanceOf(AIRecommendationService::class);
    });

    test('recommend returns array with message', function () {
        $service = new AIRecommendationService;

        $quizData = [
            'budget' => 5000,
            'gender' => 'women',
        ];

        $response = $service->recommend($quizData);

        expect($response)->toBeArray()
            ->and($response)->toHaveKey('message');
    })->group('live-api');

    test('recommend returns profile', function () {
        $service = new AIRecommendationService;

        $quizData = [
            'budget' => 5000,
            'gender' => 'women',
        ];

        $response = $service->recommend($quizData);

        expect($response)->toHaveKey('profile')
            ->and($response['profile'])->toBeArray();
    })->group('live-api');
});
