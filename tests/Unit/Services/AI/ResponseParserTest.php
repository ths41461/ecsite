<?php

/**
 * @group unit
 * @group ai
 */

use App\Services\AI\ResponseParser;

describe('ResponseParser', function () {

    test('can instantiate response parser', function () {
        $parser = new ResponseParser;

        expect($parser)->toBeInstanceOf(ResponseParser::class);
    });

    test('parseOllamaChatResponse extracts message content', function () {
        $parser = new ResponseParser;

        $ollamaResponse = [
            'model' => 'qwen3',
            'message' => [
                'role' => 'assistant',
                'content' => 'こんにちは！おすすめの香水をご紹介します。',
            ],
            'done' => true,
        ];

        $result = $parser->parseOllamaChatResponse($ollamaResponse);

        expect($result)->toBeArray()
            ->and($result['message'])->toBe('こんにちは！おすすめの香水をご紹介します。')
            ->and($result['model'])->toBe('qwen3')
            ->and($result['done'])->toBeTrue();
    });

    test('parseOllamaChatResponse extracts tool calls', function () {
        $parser = new ResponseParser;

        $ollamaResponse = [
            'model' => 'qwen3',
            'message' => [
                'role' => 'assistant',
                'content' => '',
                'tool_calls' => [
                    [
                        'function' => [
                            'name' => 'search_products',
                            'arguments' => [
                                'query' => 'floral',
                                'max_results' => 5,
                            ],
                        ],
                    ],
                ],
            ],
            'done' => false,
        ];

        $result = $parser->parseOllamaChatResponse($ollamaResponse);

        expect($result)->toHaveKey('tool_calls')
            ->and($result['tool_calls'])->toBeArray()
            ->and(count($result['tool_calls']))->toBe(1)
            ->and($result['tool_calls'][0]['function'])->toBe('search_products')
            ->and($result['tool_calls'][0]['arguments'])->toBe([
                'query' => 'floral',
                'max_results' => 5,
            ]);
    });

    test('parseOllamaChatResponse handles empty content', function () {
        $parser = new ResponseParser;

        $ollamaResponse = [
            'model' => 'qwen3',
            'message' => [
                'role' => 'assistant',
                'content' => '',
            ],
            'done' => true,
        ];

        $result = $parser->parseOllamaChatResponse($ollamaResponse);

        expect($result['message'])->toBe('');
    });

    test('parseOllamaChatResponse handles missing message key', function () {
        $parser = new ResponseParser;

        $ollamaResponse = [
            'model' => 'qwen3',
            'done' => true,
        ];

        $result = $parser->parseOllamaChatResponse($ollamaResponse);

        expect($result)->toBeArray()
            ->and($result['message'])->toBe('');
    });

    test('parseToolCallArguments decodes JSON string arguments', function () {
        $parser = new ResponseParser;

        $toolCall = [
            'function' => [
                'name' => 'search_products',
                'arguments' => '{"query":"floral","max_results":5}',
            ],
        ];

        $result = $parser->parseToolCallArguments($toolCall);

        expect($result)->toBeArray()
            ->and($result)->toBe([
                'query' => 'floral',
                'max_results' => 5,
            ]);
    });

    test('parseToolCallArguments handles array arguments', function () {
        $parser = new ResponseParser;

        $toolCall = [
            'function' => [
                'name' => 'search_products',
                'arguments' => [
                    'query' => 'woody',
                    'max_results' => 10,
                ],
            ],
        ];

        $result = $parser->parseToolCallArguments($toolCall);

        expect($result)->toBeArray()
            ->and($result)->toBe([
                'query' => 'woody',
                'max_results' => 10,
            ]);
    });

    test('parseToolCallArguments handles invalid JSON gracefully', function () {
        $parser = new ResponseParser;

        $toolCall = [
            'function' => [
                'name' => 'search_products',
                'arguments' => 'invalid json{',
            ],
        ];

        $result = $parser->parseToolCallArguments($toolCall);

        expect($result)->toBeArray()
            ->and($result)->toBeEmpty();
    });

    test('extractProductIdsFromText extracts product IDs from text', function () {
        $parser = new ResponseParser;

        $text = 'おすすめの香水は以下の通りです：商品ID 1, 2, 3 がおすすめです。';
        $maxProductId = 1000;

        $result = $parser->extractProductIdsFromText($text, $maxProductId);

        expect($result)->toBeArray()
            ->and($result)->toContain(1, 2, 3);
    });

    test('extractProductIdsFromText ignores IDs outside valid range', function () {
        $parser = new ResponseParser;

        $text = '商品ID 1, 9999, 2';
        $maxProductId = 100;

        $result = $parser->extractProductIdsFromText($text, $maxProductId);

        expect($result)->toBeArray()
            ->and($result)->toContain(1, 2)
            ->and($result)->not->toContain(9999);
    });

    test('extractProductIdsFromText returns empty array for no IDs', function () {
        $parser = new ResponseParser;

        $text = 'おすすめの香水はありません。';
        $maxProductId = 1000;

        $result = $parser->extractProductIdsFromText($text, $maxProductId);

        expect($result)->toBeArray()
            ->and($result)->toBeEmpty();
    });

    test('buildFinalResponse formats complete recommendation response', function () {
        $parser = new ResponseParser;

        $aiMessage = 'おすすめの香水をご紹介します。';
        $products = [
            ['id' => 1, 'name' => 'Test Perfume', 'brand' => 'Test Brand'],
        ];
        $profile = ['gender' => 'women', 'budget' => 5000];

        $result = $parser->buildFinalResponse($aiMessage, $products, $profile);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('message')
            ->and($result)->toHaveKey('products')
            ->and($result)->toHaveKey('profile')
            ->and($result['message'])->toBe($aiMessage)
            ->and($result['products'])->toBe($products)
            ->and($result['profile'])->toBe($profile);
    });

    test('buildFinalResponse handles empty products', function () {
        $parser = new ResponseParser;

        $result = $parser->buildFinalResponse('No products found', [], []);

        expect($result['products'])->toBeArray()
            ->and($result['products'])->toBeEmpty();
    });

    test('parseOllamaStreamingResponse parses incremental response', function () {
        $parser = new ResponseParser;

        $streamChunk = [
            'model' => 'qwen3',
            'message' => [
                'role' => 'assistant',
                'content' => 'こんにちは',
            ],
            'done' => false,
        ];

        $result = $parser->parseOllamaStreamingResponse($streamChunk);

        expect($result['content'])->toBe('こんにちは')
            ->and($result['done'])->toBeFalse()
            ->and($result)->toHaveKey('tool_calls');
    });
});
