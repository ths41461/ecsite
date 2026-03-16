<?php

use App\Http\Requests\SendChatMessageRequest;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->request = new SendChatMessageRequest;
});

it('passes validation with all required fields', function () {
    $data = [
        'session_id' => 'test-session-123',
        'message' => 'おすすめの香水を教えてください',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
});

it('passes validation with long message', function () {
    $data = [
        'session_id' => 'test-session-123',
        'message' => str_repeat('あ', 1000),
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
});

it('fails when session_id is missing', function () {
    $data = [
        'message' => 'テストメッセージ',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('session_id'))->toBeTrue();
    expect($validator->errors()->first('session_id'))->toBe('セッションIDが必要です');
});

it('fails when session_id is empty', function () {
    $data = [
        'session_id' => '',
        'message' => 'テストメッセージ',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('session_id'))->toBeTrue();
});

it('fails when session_id exceeds max length', function () {
    $data = [
        'session_id' => str_repeat('a', 256),
        'message' => 'テストメッセージ',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('session_id'))->toBeTrue();
});

it('fails when message is missing', function () {
    $data = [
        'session_id' => 'test-session-123',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('message'))->toBeTrue();
    expect($validator->errors()->first('message'))->toBe('メッセージを入力してください');
});

it('fails when message is empty', function () {
    $data = [
        'session_id' => 'test-session-123',
        'message' => '',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('message'))->toBeTrue();
});

it('fails when message exceeds max length', function () {
    $data = [
        'session_id' => 'test-session-123',
        'message' => str_repeat('あ', 1001),
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('message'))->toBeTrue();
});

it('accepts uuid format session_id', function () {
    $data = [
        'session_id' => (string) \Illuminate\Support\Str::uuid(),
        'message' => 'テストメッセージ',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
});

it('accepts various valid session_id formats', function ($sessionId) {
    $data = [
        'session_id' => $sessionId,
        'message' => 'テストメッセージ',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
})->with([
    'simple-string',
    'with-numbers-123',
    'with-underscores-test_123',
    'camelCaseSessionId',
]);

it('accepts Japanese message', function () {
    $data = [
        'session_id' => 'test-session-123',
        'message' => 'フローラル系の香りが好きです。予算は5000円くらいで、デイリー使いできるものを教えてください。',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
});

it('accepts mixed Japanese and English message', function () {
    $data = [
        'session_id' => 'test-session-123',
        'message' => 'I like floral notes. フローラル系のおすすめは？',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
});

it('accepts message with emojis', function () {
    $data = [
        'session_id' => 'test-session-123',
        'message' => '可愛い香りがいいな🌸✨',
    ];

    $validator = Validator::make($data, $this->request->rules(), $this->request->messages());

    expect($validator->passes())->toBeTrue();
});

it('authorizes all users', function () {
    expect($this->request->authorize())->toBeTrue();
});
