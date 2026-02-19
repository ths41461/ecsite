<?php

use App\Services\AI\Providers\GeminiProvider;

beforeEach(function () {
    $this->provider = new GeminiProvider;
});

describe('GeminiProvider', function () {
    describe('buildRequestPayload', function () {
        test('builds basic text request payload', function () {
            $payload = $this->provider->buildRequestPayload(
                'You are a helpful assistant.',
                'Hello, world!',
                []
            );

            expect($payload)->toHaveKey('contents');
            expect($payload)->toHaveKey('systemInstruction');
            expect($payload['contents'])->toBeArray();
            expect($payload['contents'][0])->toHaveKey('role', 'user');
            expect($payload['contents'][0]['parts'][0])->toHaveKey('text', 'Hello, world!');
        });

        test('builds request with tools', function () {
            $tools = [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'search_products',
                        'description' => 'Search products',
                        'parameters' => ['type' => 'object'],
                    ],
                ],
            ];

            $payload = $this->provider->buildRequestPayload(
                'System prompt',
                'Search for floral perfumes',
                $tools
            );

            expect($payload)->toHaveKey('tools');
            expect($payload['tools'])->toBeArray();
            expect($payload['tools'][0])->toHaveKey('functionDeclarations');
        });

        test('builds request with json response type', function () {
            $payload = $this->provider->buildRequestPayload(
                'System prompt',
                'Return JSON',
                [],
                true
            );

            expect($payload)->toHaveKey('generationConfig');
            expect($payload['generationConfig'])->toHaveKey('responseMimeType', 'application/json');
        });
    });

    describe('buildConversationContents', function () {
        test('converts conversation history to Gemini format', function () {
            $conversation = [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => 'Hello'],
                ['role' => 'assistant', 'content' => 'Hi there!'],
                ['role' => 'user', 'content' => 'How are you?'],
            ];

            $contents = $this->provider->buildConversationContents($conversation);

            expect($contents)->toBeArray();
            expect($contents)->toHaveCount(3);
            expect($contents[0])->toHaveKey('role', 'user');
            expect($contents[1])->toHaveKey('role', 'model');
            expect($contents[2])->toHaveKey('role', 'user');
        });

        test('handles function call in conversation', function () {
            $conversation = [
                ['role' => 'user', 'content' => 'Search for products'],
                ['role' => 'assistant', 'tool_calls' => [
                    ['function' => ['name' => 'search_products', 'arguments' => '{"category":"floral"}']],
                ]],
                ['role' => 'tool', 'content' => '{"count": 5}'],
            ];

            $contents = $this->provider->buildConversationContents($conversation);

            expect($contents)->toBeArray();
            expect($contents[1])->toHaveKey('parts');
            expect($contents[1]['parts'][0])->toHaveKey('functionCall');
            expect($contents[2])->toHaveKey('role', 'function');
        });
    });

    describe('getApiUrl', function () {
        test('returns primary model url by default', function () {
            $url = $this->provider->getApiUrl(false);

            expect($url)->toContain('generativelanguage.googleapis.com');
            expect($url)->toContain('gemini-2.5-flash-lite');
            expect($url)->toContain(':generateContent');
        });

        test('returns fallback model url when requested', function () {
            $url = $this->provider->getApiUrl(true);

            expect($url)->toContain('gemini-2.5-flash');
            expect($url)->not->toContain('gemini-2.5-flash-lite');
        });
    });

    describe('formatToolsForGemini', function () {
        test('converts tools to Gemini format', function () {
            $tools = [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'search_products',
                        'description' => 'Search products',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'category' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ];

            $formatted = $this->provider->formatToolsForGemini($tools);

            expect($formatted)->toBeArray();
            expect($formatted[0])->toHaveKey('functionDeclarations');
            expect($formatted[0]['functionDeclarations'][0])->toHaveKey('name', 'search_products');
        });

        test('returns empty array for empty tools', function () {
            $formatted = $this->provider->formatToolsForGemini([]);

            expect($formatted)->toBeArray();
            expect($formatted)->toBeEmpty();
        });
    });
});
