<?php

/**
 * @group live-api
 * @group ollama
 */

use App\Services\AI\Providers\OllamaProvider;

describe('OllamaProvider', function () {

    test('can instantiate ollama provider', function () {
        $provider = new OllamaProvider;
        expect($provider)->toBeInstanceOf(OllamaProvider::class);
    });

    test('chat returns response with message', function () {
        $provider = new OllamaProvider;
        $response = $provider->chat('こんにちは', []);
        expect($response)->toBeArray()
            ->and($response)->toHaveKey('message');
    })->group('live-api');

    test('chat returns model name in response', function () {
        $provider = new OllamaProvider;
        $response = $provider->chat('こんにちは', []);
        expect($response)->toHaveKey('model')
            ->and($response['model'])->toBeString();
    })->group('live-api');

    test('getModel returns configured model', function () {
        $provider = new OllamaProvider;
        expect($provider->getModel())->toBe('qwen3');
    });

    test('setModel changes the model', function () {
        $provider = new OllamaProvider;
        $provider->setModel('llama2');
        expect($provider->getModel())->toBe('llama2');
    });

    test('isAvailable returns true when ollama is running', function () {
        $provider = new OllamaProvider;
        expect($provider->isAvailable())->toBeTrue();
    })->group('live-api');

    test('chat handles connection error gracefully', function () {
        $provider = new OllamaProvider('http://invalid-host:99999');
        $response = $provider->chat('test', []);
        expect($response)->toBeArray()
            ->and($response)->toHaveKey('error');
    });
});
