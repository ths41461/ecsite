<?php

test('quiz page has concentration question', function () {
    $quizData = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => 'spring',
        'gender' => 'women',
        'concentration' => 'edp',
    ];

    $queryString = http_build_query($quizData);
    $response = $this->get("/fragrance-diagnosis/results?{$queryString}");

    $response->assertStatus(200);
});

test('quiz results page accepts concentration parameter', function () {
    $quizData = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => 'spring',
        'gender' => 'women',
        'concentration' => 'edt',
    ];

    $queryString = http_build_query($quizData);
    $response = $this->get("/fragrance-diagnosis/results?{$queryString}");

    $response->assertStatus(200);
});

test('quiz results page validates concentration parameter', function () {
    $quizData = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => 'spring',
        'gender' => 'women',
        'concentration' => 'invalid',
    ];

    $queryString = http_build_query($quizData);
    $response = $this->get("/fragrance-diagnosis/results?{$queryString}");

    $response->assertStatus(302);
});

test('quiz results page works with all concentration types', function () {
    $concentrations = ['parfum', 'edp', 'edc', 'mist'];

    foreach ($concentrations as $concentration) {
        $quizData = [
            'personality' => 'natural',
            'vibe' => 'woody',
            'occasion' => ['daily'],
            'style' => 'casual',
            'budget' => 8000,
            'experience' => 'some',
            'season' => 'all',
            'gender' => 'unisex',
            'concentration' => $concentration,
        ];

        $queryString = http_build_query($quizData);
        $response = $this->get("/fragrance-diagnosis/results?{$queryString}");

        $response->assertStatus(200, "Failed for concentration: {$concentration}");
    }
});
