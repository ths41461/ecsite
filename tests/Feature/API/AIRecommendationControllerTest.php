<?php

/**
 * @group feature
 * @group ai
 * @group live-api
 */

use App\Models\AiChatSession;
use App\Models\QuizResult;
use App\Models\User;

describe('POST /api/v1/ai/quiz', function () {
    it('returns recommendations for valid quiz submission', function () {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily', 'date'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'profile',
                    'recommendations',
                    'session_id',
                ],
            ]);
    });

    it('returns validation error for missing fields', function () {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'romantic',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    });

    it('creates quiz result in database', function () {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'cool',
            'vibe' => 'woody',
            'occasion' => ['work'],
            'style' => 'chic',
            'budget' => 8000,
            'experience' => 'intermediate',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('quiz_results', [
            'profile_type' => 'cool_woody',
        ]);
    });

    it('creates chat session for authenticated user', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/ai/quiz', [
            'personality' => 'natural',
            'vibe' => 'citrus',
            'occasion' => ['daily'],
            'style' => 'casual',
            'budget' => 3000,
            'experience' => 'beginner',
        ]);

        $response->assertStatus(200);

        $sessionId = $response->json('data.session_id');
        $this->assertDatabaseHas('ai_chat_sessions', [
            'session_token' => $sessionId,
            'user_id' => $user->id,
        ]);
    });

    it('creates chat session for guest user', function () {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'energetic',
            'vibe' => 'ocean',
            'occasion' => ['casual'],
            'style' => 'casual',
            'budget' => 10000,
            'experience' => 'advanced',
        ]);

        $response->assertStatus(200);

        $sessionId = $response->json('data.session_id');
        $this->assertDatabaseHas('ai_chat_sessions', [
            'session_token' => $sessionId,
        ]);
    });

    it('returns recommendations with product details', function () {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 10000,
            'experience' => 'beginner',
        ]);

        $response->assertStatus(200);

        $recommendations = $response->json('data.recommendations');

        if (count($recommendations) > 0) {
            expect($recommendations[0])->toHaveKeys([
                'id',
                'name',
                'brand',
                'price',
            ]);
        }
    });

    it('accepts optional season parameter', function () {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring_summer',
        ]);

        $response->assertStatus(200);
    });
});

describe('GET /api/v1/ai/recommendations', function () {
    it('returns recommendations for valid session', function () {
        $uniqueToken = 'test-rec-'.uniqid();
        $quizResult = QuizResult::create([
            'session_token' => $uniqueToken,
            'profile_type' => 'romantic_floral',
            'profile_data_json' => ['type' => 'romantic_floral', 'name' => 'テスト'],
            'answers_json' => ['budget' => 5000],
            'recommended_product_ids' => [],
        ]);

        $session = AiChatSession::create([
            'session_token' => $uniqueToken,
            'quiz_result_id' => $quizResult->id,
        ]);

        $response = $this->getJson('/api/v1/ai/recommendations?session_id='.$uniqueToken);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'profile',
                    'recommendations',
                ],
            ]);
    });

    it('returns 404 for invalid session', function () {
        $response = $this->getJson('/api/v1/ai/recommendations?session_id=non-existent-session');

        $response->assertStatus(404);
    });

    it('returns 422 when session_id is missing', function () {
        $response = $this->getJson('/api/v1/ai/recommendations');

        $response->assertStatus(422);
    });
});
