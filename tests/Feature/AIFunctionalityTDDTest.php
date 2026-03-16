<?php

use function Pest\Laravel\artisan;

beforeEach(function () {
    artisan('migrate:fresh', ['--force' => true]);
    artisan('db:seed', ['--force' => true]);
});

describe('FragranceDiagnosis E2E Flow', function () {
    test('complete quiz flow from start to results', function () {
        $quizResponse = $this->get('/fragrance-diagnosis');
        $quizResponse->assertStatus(200);
        $quizResponse->assertInertia(fn ($page) => $page
            ->component('FragranceDiagnosis')
        );

        $resultsResponse = $this->get('/fragrance-diagnosis/results?'.http_build_query([
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily', 'date'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ]));

        $resultsResponse->assertStatus(200);
        $resultsResponse->assertInertia(fn ($page) => $page
            ->component('FragranceDiagnosisResults')
            ->has('quizData')
            ->has('profile')
            ->has('recommendations')
            ->has('sessionId')
        );
    });

    test('quiz results page displays recommended products', function () {
        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
            'personality' => 'cool',
            'vibe' => 'woody',
            'occasion' => ['work', 'special'],
            'style' => 'chic',
            'budget' => 5000,
            'experience' => 'experienced',
            'season' => 'fall',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('recommendations')
        );
    });

    test('quiz results page generates unique session for chat', function () {
        $response1 = $this->get('/fragrance-diagnosis/results?'.http_build_query([
            'personality' => 'natural',
            'vibe' => 'citrus',
            'occasion' => ['casual'],
            'style' => 'natural',
            'budget' => 3000,
            'experience' => 'beginner',
            'season' => 'all',
        ]));

        $response1->assertStatus(200);
        $response1->assertInertia(fn ($page) => $page
            ->has('sessionId')
        );

        $response2 = $this->get('/fragrance-diagnosis/results?'.http_build_query([
            'personality' => 'energetic',
            'vibe' => 'ocean',
            'occasion' => ['daily'],
            'style' => 'casual',
            'budget' => 8000,
            'experience' => 'some',
            'season' => 'spring',
        ]));

        $response2->assertStatus(200);
        $response2->assertInertia(fn ($page) => $page
            ->has('sessionId')
        );
    });

    test('quiz results page respects budget filter', function () {
        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
            'personality' => 'romantic',
            'vibe' => 'vanilla',
            'occasion' => ['date'],
            'style' => 'feminine',
            'budget' => 3000,
            'experience' => 'beginner',
            'season' => 'spring',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('recommendations')
        );
    });

    test('quiz results page generates scent profile based on personality and vibe', function () {
        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
            'personality' => 'energetic',
            'vibe' => 'citrus',
            'occasion' => ['daily'],
            'style' => 'casual',
            'budget' => 5000,
            'experience' => 'some',
            'season' => 'all',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('profile')
            ->where('profile.type', 'energetic')
        );
    });
});

describe('AI Chat Integration with Quiz Results', function () {
    test('chat API accepts valid session token', function () {
        $resultsResponse = $this->get('/fragrance-diagnosis/results?'.http_build_query([
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ]));

        $resultsResponse->assertStatus(200);
        $resultsResponse->assertInertia(fn ($page) => $page
            ->has('sessionId')
        );

        $chatResponse = $this->postJson('/api/v1/ai/chat', [
            'session_id' => 'test-chat-session-'.uniqid(),
            'message' => 'この香水についてもっと教えてください',
        ]);

        $chatResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'message',
                    'session_id',
                    'timestamp',
                ],
            ]);
    });

    test('chat validates session_id is required', function () {
        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'テストメッセージ',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['session_id']);
    });

    test('chat validates message is required', function () {
        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => 'test-session',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    });

    test('chat validates message max length', function () {
        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => 'test-session',
            'message' => str_repeat('a', 1001),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    });
});

describe('Quiz API Endpoint', function () {
    test('quiz API accepts valid quiz data', function () {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'cool',
            'vibe' => 'woody',
            'occasion' => ['work', 'special'],
            'style' => 'chic',
            'budget' => 8000,
            'experience' => 'advanced',
            'season' => 'fall_winter',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'recommendations',
                    'profile',
                ],
            ]);
    });

    test('quiz API validates personality parameter', function () {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'invalid',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['personality']);
    });

    test('quiz API validates vibe parameter', function () {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'romantic',
            'vibe' => 'invalid',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['vibe']);
    });

    test('quiz API validates occasion is array', function () {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => 'daily',
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['occasion']);
    });

    test('quiz API validates budget is integer', function () {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 'not-a-number',
            'experience' => 'beginner',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['budget']);
    });
});
