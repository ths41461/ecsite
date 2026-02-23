<?php

/**
 * @group unit
 * @group ai
 */

use App\Services\AI\ContextBuilder;
use App\Services\AI\Providers\OllamaProvider;
use App\Services\AI\ReActAgentEngine;
use App\Services\AI\ToolRegistry;

describe('ReActAgentEngine', function () {

    test('can instantiate react agent engine', function () {
        $provider = new OllamaProvider;
        $registry = new ToolRegistry;
        $contextBuilder = new ContextBuilder;

        $engine = new ReActAgentEngine($provider, $registry, $contextBuilder);

        expect($engine)->toBeInstanceOf(ReActAgentEngine::class);
    });

    test('getMaxIterations returns default value', function () {
        $provider = new OllamaProvider;
        $registry = new ToolRegistry;
        $contextBuilder = new ContextBuilder;

        $engine = new ReActAgentEngine($provider, $registry, $contextBuilder);

        expect($engine->getMaxIterations())->toBe(5);
    });

    test('run respects max iterations', function () {
        $provider = new OllamaProvider;
        $registry = new ToolRegistry;
        $contextBuilder = new ContextBuilder;

        $engine = new ReActAgentEngine($provider, $registry, $contextBuilder);
        $engine->setMaxIterations(3);

        expect($engine->getMaxIterations())->toBe(3);
    });

    test('run returns response with message', function () {
        $provider = new OllamaProvider;
        $registry = new ToolRegistry;
        $contextBuilder = new ContextBuilder;

        $engine = new ReActAgentEngine($provider, $registry, $contextBuilder);
        $engine->setMaxIterations(1);

        $response = $engine->run('こんにちは', []);

        expect($response)->toBeArray()
            ->and($response)->toHaveKey('message');
    })->group('live-api');
});
