<?php

use App\Services\AI\ReActAgentEngine;
use App\Services\AI\ResponseParser;
use App\Services\AI\ToolRegistry;

describe('ReActAgentEngine', function () {
    beforeEach(function () {
        $this->registry = new ToolRegistry;
        $this->parser = new ResponseParser;
    });

    describe('buildSystemPrompt', function () {
        test('builds prompt with user context', function () {
            $engine = new ReActAgentEngine($this->registry);

            $context = [
                'user_profile' => [
                    'personality' => 'romantic',
                    'vibe' => 'floral',
                    'budget' => 5000,
                ],
                'available_products' => [],
            ];

            $prompt = $engine->buildSystemPrompt($context);

            expect($prompt)->toBeString();
            expect($prompt)->toContain('romantic');
            expect($prompt)->toContain('floral');
            expect($prompt)->toContain('5000');
            expect($prompt)->toContain('search_products');
            expect($prompt)->toContain('check_inventory');
        });
    });

    describe('executeTool', function () {
        test('executes search_products tool', function () {
            $engine = new ReActAgentEngine($this->registry);

            $toolCall = [
                'function' => [
                    'name' => 'search_products',
                    'arguments' => '{"category":"floral","max_price":5000}',
                ],
            ];

            $result = $engine->executeTool($toolCall);

            expect($result)->toHaveKey('count');
            expect($result)->toHaveKey('products');
        });

        test('executes check_inventory tool', function () {
            $engine = new ReActAgentEngine($this->registry);

            $toolCall = [
                'function' => [
                    'name' => 'check_inventory',
                    'arguments' => '{"product_ids":[1,2]}',
                ],
            ];

            $result = $engine->executeTool($toolCall);

            expect($result)->toHaveKey('inventory');
        });

        test('throws on unknown tool', function () {
            $engine = new ReActAgentEngine($this->registry);

            $toolCall = [
                'function' => [
                    'name' => 'unknown_tool',
                    'arguments' => '{}',
                ],
            ];

            expect(fn () => $engine->executeTool($toolCall))
                ->toThrow(\InvalidArgumentException::class);
        });

        test('handles invalid json arguments', function () {
            $engine = new ReActAgentEngine($this->registry);

            $toolCall = [
                'function' => [
                    'name' => 'search_products',
                    'arguments' => 'invalid json',
                ],
            ];

            expect(fn () => $engine->executeTool($toolCall))
                ->toThrow(\RuntimeException::class);
        });
    });

    describe('parseFinalResponse', function () {
        test('parses json from markdown code block', function () {
            $engine = new ReActAgentEngine($this->registry);

            $content = 'Here are the recommendations:
```json
{
  "profile": {"type": "fresh_girly"},
  "recommendations": [{"product_id": 1}]
}
```';

            $result = $engine->parseFinalResponse($content);

            expect($result)->toHaveKey('profile');
            expect($result)->toHaveKey('recommendations');
            expect($result['profile']['type'])->toBe('fresh_girly');
        });

        test('parses plain json', function () {
            $engine = new ReActAgentEngine($this->registry);

            $content = '{"profile":{"type":"romantic"},"recommendations":[]}';

            $result = $engine->parseFinalResponse($content);

            expect($result)->toHaveKey('profile');
            expect($result['profile']['type'])->toBe('romantic');
        });

        test('throws on invalid json', function () {
            $engine = new ReActAgentEngine($this->registry);

            $content = 'This is not valid JSON';

            expect(fn () => $engine->parseFinalResponse($content))
                ->toThrow(\RuntimeException::class);
        });
    });

    describe('maxIterations', function () {
        test('default max iterations is 5', function () {
            $engine = new ReActAgentEngine($this->registry);
            expect($engine->getMaxIterations())->toBe(5);
        });

        test('can set custom max iterations', function () {
            $engine = new ReActAgentEngine($this->registry, 10);
            expect($engine->getMaxIterations())->toBe(10);
        });
    });
});
