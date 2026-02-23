<?php

use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

test('results page returns 200 status', function () {
    $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => 'spring',
    ]));

    $response->assertStatus(200);
});

test('results page returns inertia component', function () {
    $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => 'spring',
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('FragranceDiagnosisResults')
    );
});

test('results page receives quiz data as props', function () {
    $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily', 'date'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => 'spring',
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('FragranceDiagnosisResults')
        ->has('quizData')
        ->where('quizData.personality', 'romantic')
        ->where('quizData.vibe', 'floral')
        ->where('quizData.style', 'feminine')
        ->where('quizData.budget', 5000)
        ->where('quizData.experience', 'beginner')
        ->where('quizData.season', 'spring')
    );
});

test('results page receives scent profile', function () {
    $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => 'spring',
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('FragranceDiagnosisResults')
        ->has('profile')
        ->has('profile.type')
        ->has('profile.name')
        ->has('profile.description')
    );
});

test('results page receives product recommendations', function () {
    Product::factory()->count(3)->create([
        'is_active' => true,
    ]);

    $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => 'spring',
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('FragranceDiagnosisResults')
        ->has('recommendations')
    );
});

test('results page receives session id for chat', function () {
    $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
        'season' => 'spring',
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('FragranceDiagnosisResults')
        ->has('sessionId')
    );
});

test('results page handles missing optional parameters', function () {
    $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
    ]));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('FragranceDiagnosisResults')
    );
});

test('results page validates required parameters', function () {
    $response = $this->get('/fragrance-diagnosis/results');

    $response->assertStatus(302);
});

test('results page respects budget filter in recommendations', function () {
    $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 3000,
        'experience' => 'beginner',
        'season' => 'spring',
    ]));

    $response->assertStatus(200);
});
