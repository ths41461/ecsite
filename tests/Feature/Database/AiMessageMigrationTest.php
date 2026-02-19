<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('ai_messages table has correct columns', function () {
    $this->assertTrue(Schema::hasTable('ai_messages'));

    $this->assertTrue(Schema::hasColumn('ai_messages', 'id'));
    $this->assertTrue(Schema::hasColumn('ai_messages', 'session_id'));
    $this->assertTrue(Schema::hasColumn('ai_messages', 'role'));
    $this->assertTrue(Schema::hasColumn('ai_messages', 'content'));
    $this->assertTrue(Schema::hasColumn('ai_messages', 'metadata_json'));
    $this->assertTrue(Schema::hasColumn('ai_messages', 'created_at'));
    $this->assertTrue(Schema::hasColumn('ai_messages', 'updated_at'));

    $dbName = DB::connection()->getDatabaseName();
    $foreignKeys = DB::select("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = ? 
        AND TABLE_NAME = 'ai_messages' 
        AND COLUMN_NAME = 'session_id' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ", [$dbName]);
    $this->assertGreaterThan(0, count($foreignKeys), 'Foreign key constraint on session_id should exist');
});

test('ai_message model can create record', function () {
    $session = \App\Models\AiChatSession::create([
        'session_token' => 'test-session-for-messages',
        'context_json' => ['test' => 'data'],
    ]);

    $message = \App\Models\AiMessage::create([
        'session_id' => $session->id,
        'role' => 'user',
        'content' => 'Hello, I need help finding a perfume.',
        'metadata_json' => ['source' => 'quiz'],
    ]);

    $this->assertDatabaseHas('ai_messages', [
        'session_id' => $session->id,
        'role' => 'user',
    ]);

    expect($message->role)->toBe('user');
    expect($message->content)->toBe('Hello, I need help finding a perfume.');
    expect($message->metadata_json)->toBe(['source' => 'quiz']);
});

test('ai_message model has correct fillable attributes', function () {
    $model = new \App\Models\AiMessage;
    $fillable = $model->getFillable();

    expect($fillable)->toContain('session_id');
    expect($fillable)->toContain('role');
    expect($fillable)->toContain('content');
    expect($fillable)->toContain('metadata_json');
});

test('ai_message model casts metadata_json as array', function () {
    $session = \App\Models\AiChatSession::create([
        'session_token' => 'test-session-for-casting',
        'context_json' => [],
    ]);

    $message = \App\Models\AiMessage::create([
        'session_id' => $session->id,
        'role' => 'assistant',
        'content' => 'Here are some recommendations.',
        'metadata_json' => ['products' => [1, 2, 3], 'confidence' => 0.95],
    ]);

    $retrieved = \App\Models\AiMessage::find($message->id);
    expect($retrieved->metadata_json)->toBeArray();
    expect($retrieved->metadata_json['products'])->toBe([1, 2, 3]);
    expect($retrieved->metadata_json['confidence'])->toBe(0.95);
});

test('ai_message belongs to ai_chat_session', function () {
    $session = \App\Models\AiChatSession::create([
        'session_token' => 'test-session-for-relation',
        'context_json' => [],
    ]);

    $message = \App\Models\AiMessage::create([
        'session_id' => $session->id,
        'role' => 'user',
        'content' => 'Test message',
    ]);

    expect($message->session)->toBeInstanceOf(\App\Models\AiChatSession::class);
    expect($message->session->id)->toBe($session->id);
});

test('ai_message role must be valid enum value', function () {
    $session = \App\Models\AiChatSession::create([
        'session_token' => 'test-session-for-enum',
        'context_json' => [],
    ]);

    $validRoles = ['user', 'assistant', 'system'];

    foreach ($validRoles as $role) {
        $message = \App\Models\AiMessage::create([
            'session_id' => $session->id,
            'role' => $role,
            'content' => "Message with role: {$role}",
        ]);
        expect($message->role)->toBe($role);
    }
});
