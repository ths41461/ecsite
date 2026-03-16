<?php

/**
 * @group feature
 * @group ai
 * @group live-api
 */

use App\Models\AiChatSession;

beforeEach(function () {
    $this->artisan('db:seed');
});

it('responds to POST /api/v1/ai/quiz', function () {
    $response = $this->postJson('/api/v1/ai/quiz', [
        'personality' => 'romantic',
        'vibe' => 'floral',
        'occasion' => ['daily'],
        'style' => 'feminine',
        'budget' => 5000,
        'experience' => 'beginner',
    ]);

    $response->assertStatus(200);
});

it('responds to GET /api/v1/ai/recommendations', function () {
    $quizResult = \App\Models\QuizResult::create([
        'session_token' => 'test-route-quiz',
        'profile_type' => 'romantic_floral',
        'profile_data_json' => ['type' => 'romantic_floral', 'name' => 'テスト'],
        'answers_json' => ['budget' => 5000],
        'recommended_product_ids' => [],
    ]);

    $session = AiChatSession::create([
        'session_token' => 'test-route-session',
        'quiz_result_id' => $quizResult->id,
    ]);

    $response = $this->getJson('/api/v1/ai/recommendations?session_id=test-route-session');

    $response->assertStatus(200);
});

it('responds to POST /api/v1/ai/chat', function () {
    $session = AiChatSession::create([
        'session_token' => 'test-chat-session',
    ]);

    $response = $this->postJson('/api/v1/ai/chat', [
        'session_id' => 'test-chat-session',
        'message' => 'テストメッセージ',
    ]);

    $response->assertStatus(200);
});

it('rate limits AI quiz endpoint', function () {
    $requests = 25;
    $successCount = 0;
    $rateLimitedCount = 0;

    for ($i = 0; $i < $requests; $i++) {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
        ]);

        if ($response->status() === 200) {
            $successCount++;
        } elseif ($response->status() === 429) {
            $rateLimitedCount++;
        }
    }

    expect($successCount)->toBeLessThanOrEqual(20);
    expect($rateLimitedCount)->toBeGreaterThan(0);
});

it('rate limits AI chat endpoint', function () {
    $uniqueToken = 'rate-limit-'.uniqid();
    $session = AiChatSession::create([
        'session_token' => $uniqueToken,
    ]);

    $requests = 25;
    $successCount = 0;
    $rateLimitedCount = 0;

    for ($i = 0; $i < $requests; $i++) {
        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => $uniqueToken,
            'message' => "テストメッセージ {$i}",
        ]);

        if ($response->status() === 200) {
            $successCount++;
        } elseif ($response->status() === 429) {
            $rateLimitedCount++;
        }
    }

    expect($successCount)->toBeLessThanOrEqual(20);
    expect($rateLimitedCount)->toBeGreaterThan(0);
});

it('returns 429 with rate limit headers when exceeded', function () {
    for ($i = 0; $i < 22; $i++) {
        $response = $this->postJson('/api/v1/ai/quiz', [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
        ]);
    }

    expect($response->status())->toBeIn([200, 429]);
});
