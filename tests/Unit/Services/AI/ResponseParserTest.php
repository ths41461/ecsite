<?php

use App\Services\AI\ResponseParser;

beforeEach(function () {
    $this->parser = new ResponseParser;
});

describe('ResponseParser', function () {
    describe('parseGeminiResponse', function () {
        test('extracts text from simple response', function () {
            $response = [
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Hello, this is a test response.'],
                            ],
                            'role' => 'model',
                        ],
                        'finishReason' => 'STOP',
                    ],
                ],
            ];

            $result = $this->parser->parseGeminiResponse($response);

            expect($result)->toHaveKey('type', 'text');
            expect($result)->toHaveKey('content', 'Hello, this is a test response.');
        });

        test('detects function call response', function () {
            $response = [
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'functionCall' => [
                                        'name' => 'search_products',
                                        'args' => ['category' => 'floral', 'max_price' => 5000],
                                    ],
                                ],
                            ],
                            'role' => 'model',
                        ],
                        'finishReason' => 'STOP',
                    ],
                ],
            ];

            $result = $this->parser->parseGeminiResponse($response);

            expect($result)->toHaveKey('type', 'function_call');
            expect($result)->toHaveKey('function_name', 'search_products');
            expect($result)->toHaveKey('function_args');
            expect($result['function_args'])->toBe(['category' => 'floral', 'max_price' => 5000]);
        });

        test('extracts json from markdown code blocks', function () {
            $text = 'Here are the recommendations:
```json
{
  "recommendations": [
    {"product_id": 1, "match_score": 95}
  ]
}
```';

            $result = $this->parser->extractJsonFromMarkdown($text);

            expect($result)->toBeArray();
            expect($result)->toHaveKey('recommendations');
        });

        test('returns raw text if no json code block found', function () {
            $text = 'This is just plain text without JSON.';

            $result = $this->parser->extractJsonFromMarkdown($text);

            expect($result)->toBeNull();
        });

        test('parses valid recommendation json', function () {
            $json = json_encode([
                'profile' => [
                    'type' => 'fresh_girly',
                    'name' => 'Fresh & Girly',
                    'description' => 'A light and fresh scent profile',
                ],
                'recommendations' => [
                    ['product_id' => 1, 'match_score' => 95, 'explanation' => 'Great match!'],
                ],
            ]);

            $result = $this->parser->parseRecommendationJson($json);

            expect($result)->toHaveKey('profile');
            expect($result)->toHaveKey('recommendations');
            expect($result['profile']['type'])->toBe('fresh_girly');
            expect($result['recommendations'])->toHaveCount(1);
        });

        test('throws on invalid json', function () {
            $invalidJson = 'this is not valid json {{{';

            expect(fn () => $this->parser->parseRecommendationJson($invalidJson))
                ->toThrow(\RuntimeException::class, 'Invalid JSON in AI response');
        });

        test('handles empty response gracefully', function () {
            $response = [
                'candidates' => [],
            ];

            $result = $this->parser->parseGeminiResponse($response);

            expect($result)->toHaveKey('type', 'empty');
        });

        test('handles blocked response', function () {
            $response = [
                'promptFeedback' => [
                    'blockReason' => 'SAFETY',
                ],
            ];

            $result = $this->parser->parseGeminiResponse($response);

            expect($result)->toHaveKey('type', 'blocked');
            expect($result)->toHaveKey('reason', 'SAFETY');
        });
    });

    describe('parseError', function () {
        test('extracts error details from gemini error response', function () {
            $errorResponse = [
                'error' => [
                    'code' => 429,
                    'message' => 'Resource has been exhausted (e.g. check quota).',
                    'status' => 'RESOURCE_EXHAUSTED',
                ],
            ];

            $result = $this->parser->parseError($errorResponse);

            expect($result)->toHaveKey('code', 429);
            expect($result)->toHaveKey('message');
            expect($result)->toHaveKey('status', 'RESOURCE_EXHAUSTED');
            expect($result)->toHaveKey('is_rate_limit', true);
        });

        test('identifies invalid api key error', function () {
            $errorResponse = [
                'error' => [
                    'code' => 400,
                    'message' => 'API key not valid.',
                    'status' => 'INVALID_ARGUMENT',
                ],
            ];

            $result = $this->parser->parseError($errorResponse);

            expect($result)->toHaveKey('code', 400);
            expect($result)->toHaveKey('is_auth_error', true);
        });
    });
});
