<?php

test('quiz page loads successfully', function () {
    $response = $this->get('/fragrance-diagnosis');
    $response->assertStatus(200);
});

test('quiz page renders inertia component', function () {
    $response = $this->get('/fragrance-diagnosis');
    $response->assertInertia(fn ($page) => $page->component('FragranceDiagnosis'));
});

test('quiz page returns 200 for results with gender parameter', function () {
    $quizData = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => 'spring',
        'gender' => 'women',
    ];

    $queryString = http_build_query($quizData);
    $response = $this->get("/fragrance-diagnosis/results?{$queryString}");

    $response->assertStatus(200);
});

test('quiz results page validates gender parameter', function () {
    $quizData = [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => 'spring',
        'gender' => 'invalid',
    ];

    $queryString = http_build_query($quizData);
    $response = $this->get("/fragrance-diagnosis/results?{$queryString}");

    $response->assertStatus(302);
});

test('quiz results page works with unisex gender', function () {
    $quizData = [
        'personality' => 'natural',
        'vibe' => 'woody',
        'occasion' => ['daily', 'work'],
        'style' => 'casual',
        'budget' => 8000,
        'experience' => 'some',
        'season' => 'all',
        'gender' => 'unisex',
    ];

    $queryString = http_build_query($quizData);
    $response = $this->get("/fragrance-diagnosis/results?{$queryString}");

    $response->assertStatus(200);
});

test('quiz results page works with men gender', function () {
    $quizData = [
        'personality' => 'cool',
        'vibe' => 'citrus',
        'occasion' => ['work', 'casual'],
        'style' => 'chic',
        'budget' => 10000,
        'experience' => 'experienced',
        'season' => 'fall',
        'gender' => 'men',
    ];

    $queryString = http_build_query($quizData);
    $response = $this->get("/fragrance-diagnosis/results?{$queryString}");

    $response->assertStatus(200);
});
