<?php

use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\ToolRegistry;
use Illuminate\Support\Facades\Http;

describe('GeminiProvider Live API Tests', function () {
    beforeEach(function () {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey) || $apiKey === 'your_key_here') {
            $this->markTestSkipped('GEMINI_API_KEY not configured');
        }

        if (! getenv('RUN_LIVE_API_TESTS')) {
            $this->markTestSkipped('Set RUN_LIVE_API_TESTS=1 to run live API tests');
        }

        $this->provider = new GeminiProvider;
        $this->registry = new ToolRegistry;
    });

    test('calls real gemini api and returns text response', function () {
        $response = $this->provider->generate(
            'You are a helpful assistant. Respond briefly.',
            'Say hello in Japanese.',
            []
        );

        expect($response)->toHaveKey('type');
        expect($response['type'])->toBe('text');
        expect($response)->toHaveKey('content');
        expect($response['content'])->toBeString();
        expect(strlen($response['content']))->toBeGreaterThan(0);
    })->group('live-api', 'gemini');

    test('calls gemini with function calling', function () {
        $tools = $this->registry->getDefinitions();

        $response = $this->provider->generate(
            'You are a fragrance consultant. Use tools to search for products.',
            'Search for floral perfumes under 5000 yen',
            $tools
        );

        expect($response)->toHaveKey('type');

        if ($response['type'] === 'function_call') {
            expect($response)->toHaveKey('function_name');
            expect($response['function_name'])->toBe('search_products');
            expect($response)->toHaveKey('function_args');
        }
    })->group('live-api', 'gemini');

    test('handles multi-turn conversation with tools', function () {
        $tools = $this->registry->getDefinitions();

        $conversation = [
            ['role' => 'system', 'content' => 'You are a fragrance consultant. Use tools when needed.'],
            ['role' => 'user', 'content' => 'Search for floral perfumes under 5000 yen'],
            [
                'role' => 'assistant',
                'tool_calls' => [
                    ['function' => ['name' => 'search_products', 'arguments' => '{"category":"floral","max_price":5000}']],
                ],
            ],
            ['role' => 'tool', 'content' => '{"count":3,"products":[{"id":1,"name":"Floral Dream","price":3500}]}', 'tool_name' => 'search_products'],
        ];

        $response = $this->provider->generateWithTools($conversation, $tools);

        expect($response)->toHaveKey('type');
        expect($response)->toHaveKey('content');
    })->group('live-api', 'gemini');

    test('chat returns japanese response', function () {
        $context = [
            'profile_type' => 'fresh_girly',
            'budget' => 5000,
            'quiz_context' => ['personality' => 'romantic'],
        ];

        $response = $this->provider->chat('私におすすめの香水を教えてください', $context);

        expect($response)->toHaveKey('message');
        expect($response['message'])->toBeString();
        expect(strlen($response['message']))->toBeGreaterThan(0);
    })->group('live-api', 'gemini');

    test('generates json response when configured', function () {
        $provider = new GeminiProvider;
        $response = Http::timeout(60)->post(
            $provider->getApiUrl(false),
            [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => 'Return a JSON object with fields: name, age']]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ]
        );

        if (! $response->successful()) {
            $this->markTestSkipped('API returned error: '.$response->status());
        }

        $data = $response->json();
        expect($data)->toHaveKey('candidates');

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $parsed = json_decode($text, true);
        expect($parsed)->toBeArray();
    })->group('live-api', 'gemini');
});
