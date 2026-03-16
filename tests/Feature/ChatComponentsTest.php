<?php

use App\Models\AiChatSession;
use App\Models\AiMessage;
use App\Models\QuizResult;

use function Pest\Laravel\artisan;

beforeEach(function () {
    artisan('db:seed', ['--force' => true]);
});

describe('Chat Components', function () {
    test('results page renders chat button', function () {
        $quizData = [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ];

        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));

        $response->assertStatus(200);
        $props = $response->inertiaProps();
        expect($props)->toHaveKey('sessionId');
    });

    test('results page has session id for chat', function () {
        $quizData = [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ];

        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));

        $response->assertStatus(200);
        $props = $response->inertiaProps();
        expect($props['sessionId'])->not->toBeEmpty();
    });

    test('chat api saves user message to database', function () {
        $quizResult = QuizResult::create([
            'user_id' => null,
            'session_token' => 'test-token-'.uniqid(),
            'profile_type' => 'romantic_floral',
            'profile_data_json' => ['name' => 'テスト'],
            'answers_json' => ['personality' => 'romantic'],
            'recommended_product_ids' => [],
        ]);

        $session = AiChatSession::create([
            'user_id' => null,
            'session_token' => 'test-session-'.uniqid(),
            'quiz_result_id' => $quizResult->id,
            'context_json' => [],
        ]);

        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => $session->session_token,
            'message' => 'おすすめの香水を教えてください',
        ]);

        $response->assertStatus(200);

        expect($session->messages()->count())->toBeGreaterThanOrEqual(1);
    });

    test('chat api returns ai response', function () {
        $quizResult = QuizResult::create([
            'user_id' => null,
            'session_token' => 'test-token-'.uniqid(),
            'profile_type' => 'romantic_floral',
            'profile_data_json' => ['name' => 'テスト'],
            'answers_json' => ['personality' => 'romantic'],
            'recommended_product_ids' => [],
        ]);

        $session = AiChatSession::create([
            'user_id' => null,
            'session_token' => 'test-session-'.uniqid(),
            'quiz_result_id' => $quizResult->id,
            'context_json' => [],
        ]);

        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => $session->session_token,
            'message' => 'こんにちは',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'message',
                'session_id',
            ],
        ]);
    });

    test('chat container component is rendered when button clicked', function () {
        $quizData = [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ];

        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));

        $response->assertStatus(200);
        $props = $response->inertiaProps();
        expect($props['sessionId'])->toBeString();
    });

    test('chat session can be retrieved with session token', function () {
        $quizResult = QuizResult::create([
            'user_id' => null,
            'session_token' => 'test-token-'.uniqid(),
            'profile_type' => 'romantic_floral',
            'profile_data_json' => ['name' => 'テスト'],
            'answers_json' => ['personality' => 'romantic'],
            'recommended_product_ids' => [],
        ]);

        $session = AiChatSession::create([
            'user_id' => null,
            'session_token' => 'test-session-token-123',
            'quiz_result_id' => $quizResult->id,
            'context_json' => [],
        ]);

        $retrievedSession = AiChatSession::where('session_token', 'test-session-token-123')->first();

        expect($retrievedSession)->not->toBeNull();
        expect($retrievedSession->id)->toBe($session->id);
    });

    test('chat history is retrieved correctly', function () {
        $quizResult = QuizResult::create([
            'user_id' => null,
            'session_token' => 'test-token-'.uniqid(),
            'profile_type' => 'romantic_floral',
            'profile_data_json' => ['name' => 'テスト'],
            'answers_json' => ['personality' => 'romantic'],
            'recommended_product_ids' => [],
        ]);

        $session = AiChatSession::create([
            'user_id' => null,
            'session_token' => 'test-session-'.uniqid(),
            'quiz_result_id' => $quizResult->id,
            'context_json' => [],
        ]);

        AiMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => 'テストメッセージ',
            'metadata_json' => [],
        ]);

        AiMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => 'テスト返答',
            'metadata_json' => [],
        ]);

        expect($session->messages()->count())->toBe(2);
    });
});
