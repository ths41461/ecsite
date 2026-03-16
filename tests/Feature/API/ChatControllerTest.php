<?php

/**
 * @group feature
 * @group ai
 * @group live-api
 */

use App\Models\AiChatSession;

describe('POST /api/v1/ai/chat', function () {
    it('sends chat message and returns AI response', function () {
        $uniqueToken = 'test-chat-'.uniqid();
        $session = AiChatSession::create([
            'session_token' => $uniqueToken,
        ]);

        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => $uniqueToken,
            'message' => 'おすすめの香水を教えてください',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'message',
                    'session_id',
                    'timestamp',
                ],
            ]);
    });

    it('saves user message to chat history', function () {
        $uniqueToken = 'test-chat-'.uniqid();
        $session = AiChatSession::create([
            'session_token' => $uniqueToken,
        ]);

        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => $uniqueToken,
            'message' => 'テストメッセージ',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ai_messages', [
            'session_id' => $session->id,
            'role' => 'user',
            'content' => 'テストメッセージ',
        ]);
    });

    it('saves AI response to chat history', function () {
        $uniqueToken = 'test-chat-'.uniqid();
        $session = AiChatSession::create([
            'session_token' => $uniqueToken,
        ]);

        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => $uniqueToken,
            'message' => 'おすすめは？',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ai_messages', [
            'session_id' => $session->id,
            'role' => 'assistant',
        ]);
    });

    it('returns 404 for non-existent session', function () {
        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => 'non-existent-session-id',
            'message' => 'テストメッセージ',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'セッションが見つかりません',
            ]);
    });

    it('returns validation error when session_id is missing', function () {
        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'テストメッセージ',
        ]);

        $response->assertStatus(422);
    });

    it('returns validation error when message is missing', function () {
        $uniqueToken = 'test-chat-'.uniqid();
        $session = AiChatSession::create([
            'session_token' => $uniqueToken,
        ]);

        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => $uniqueToken,
        ]);

        $response->assertStatus(422);
    });

    it('returns validation error when message exceeds max length', function () {
        $uniqueToken = 'test-chat-'.uniqid();
        $session = AiChatSession::create([
            'session_token' => $uniqueToken,
        ]);

        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => $uniqueToken,
            'message' => str_repeat('あ', 1001),
        ]);

        $response->assertStatus(422);
    });

    it('works with authenticated user session', function () {
        $user = \App\Models\User::factory()->create();
        $uniqueToken = 'test-chat-'.uniqid();

        $session = AiChatSession::create([
            'session_token' => $uniqueToken,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/ai/chat', [
            'session_id' => $uniqueToken,
            'message' => 'おすすめの香水は？',
        ]);

        $response->assertStatus(200);
    });

    it('returns session_id in response', function () {
        $uniqueToken = 'test-chat-'.uniqid();
        $session = AiChatSession::create([
            'session_token' => $uniqueToken,
        ]);

        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => $uniqueToken,
            'message' => 'こんにちは',
        ]);

        $response->assertStatus(200);
        expect($response->json('data.session_id'))->toBe($uniqueToken);
    });

    it('returns timestamp in ISO 8601 format', function () {
        $uniqueToken = 'test-chat-'.uniqid();
        $session = AiChatSession::create([
            'session_token' => $uniqueToken,
        ]);

        $response = $this->postJson('/api/v1/ai/chat', [
            'session_id' => $uniqueToken,
            'message' => 'テスト',
        ]);

        $response->assertStatus(200);

        $timestamp = $response->json('data.timestamp');
        expect($timestamp)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
    });
});
