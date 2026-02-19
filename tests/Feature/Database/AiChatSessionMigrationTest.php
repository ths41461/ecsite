<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('ai_chat_sessions table has correct columns', function () {
    // Verify table exists
    $this->assertTrue(Schema::hasTable('ai_chat_sessions'));

    // Verify columns exist
    $this->assertTrue(Schema::hasColumn('ai_chat_sessions', 'id'));
    $this->assertTrue(Schema::hasColumn('ai_chat_sessions', 'user_id'));
    $this->assertTrue(Schema::hasColumn('ai_chat_sessions', 'session_token'));
    $this->assertTrue(Schema::hasColumn('ai_chat_sessions', 'quiz_result_id'));
    $this->assertTrue(Schema::hasColumn('ai_chat_sessions', 'context_json'));
    $this->assertTrue(Schema::hasColumn('ai_chat_sessions', 'created_at'));
    $this->assertTrue(Schema::hasColumn('ai_chat_sessions', 'updated_at'));

    // Verify unique index on session_token
    $indexes = DB::select('SHOW INDEX FROM ai_chat_sessions');
    $indexNames = collect($indexes)->pluck('Key_name')->toArray();
    $this->assertContains('ai_chat_sessions_session_token_unique', $indexNames);
});

test('ai_chat_session model can create record', function () {
    $session = \App\Models\AiChatSession::create([
        'session_token' => 'test-token-123',
        'context_json' => ['key' => 'value'],
    ]);

    $this->assertDatabaseHas('ai_chat_sessions', [
        'session_token' => 'test-token-123',
    ]);

    expect($session->session_token)->toBe('test-token-123');
    expect($session->context_json)->toBe(['key' => 'value']);
});

test('ai_chat_session model has correct fillable attributes', function () {
    $model = new \App\Models\AiChatSession;
    $fillable = $model->getFillable();

    expect($fillable)->toContain('user_id');
    expect($fillable)->toContain('session_token');
    expect($fillable)->toContain('quiz_result_id');
    expect($fillable)->toContain('context_json');
});

test('ai_chat_session model casts context_json as array', function () {
    $session = \App\Models\AiChatSession::create([
        'session_token' => 'test-token-456',
        'context_json' => ['budget' => 5000, 'personality' => 'romantic'],
    ]);

    // When retrieved, should be array not string
    $retrieved = \App\Models\AiChatSession::find($session->id);
    expect($retrieved->context_json)->toBeArray();
    expect($retrieved->context_json['budget'])->toBe(5000);
});
