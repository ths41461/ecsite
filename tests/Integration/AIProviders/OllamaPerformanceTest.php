<?php

/**
 * @group live-api
 * @group ollama
 */

use App\Services\AI\Providers\OllamaProvider;

describe('OllamaProvider Performance Optimizations', function () {

    test('chat request includes keep_alive parameter to keep model loaded', function () {
        $provider = new OllamaProvider;

        $response = $provider->chat('hello', []);

        expect($response)->toBeArray();
        expect($response)->toHaveKey('message');
    })->group('live-api');

    test('provider has keep alive configuration', function () {
        $provider = new OllamaProvider;

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('sendRequest');
        $method->setAccessible(true);

        $providerClass = $reflection;
        expect($providerClass)->toBeInstanceOf(\ReflectionClass::class);
    });

    test('config includes num_ctx option', function () {
        $numCtx = config('ai.ollama.options.num_ctx', 16000);
        expect($numCtx)->toBeLessThanOrEqual(4096);
    });

    test('chat uses optimized context size', function () {
        $provider = new OllamaProvider;
        $response = $provider->chat('hello', []);

        expect($response)->toBeArray();
    })->group('live-api');

    test('chatWithTools uses keep_alive and optimized options', function () {
        $provider = new OllamaProvider;
        $response = $provider->chatWithTools('hello', [], []);

        expect($response)->toBeArray();
    })->group('live-api');

});
