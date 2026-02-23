<?php

/**
 * @group live-api
 * @group ollama
 */

use App\Services\AI\Providers\OllamaProvider;

describe('OllamaProvider Model Fallback', function () {

    test('primary model is used first', function () {
        $provider = new OllamaProvider(null, 'qwen3');

        expect($provider->getModel())->toBe('qwen3');
    });

    test('can fallback to secondary model', function () {
        $provider = new OllamaProvider;

        $provider->setModel('llama2');

        $response = $provider->chat('こんにちは', []);

        expect($response)->toBeArray()
            ->and($response)->toHaveKey('message')
            ->and($response['model'])->toBe('llama2');
    })->group('live-api', 'ollama');

    test('chatWithFallback tries primary model first', function () {
        $provider = new OllamaProvider(null, 'qwen3:8b');

        $response = $provider->chatWithFallback('こんにちは', []);

        expect($response)->toBeArray()
            ->and($response)->toHaveKey('message')
            ->and($response)->toHaveKey('model_used')
            ->and($response['model_used'])->toBe('qwen3:8b');
    })->group('live-api', 'ollama');

    test('chatWithFallback falls back to second model on failure', function () {
        $provider = new OllamaProvider('http://invalid-host:99999', 'invalid-model');

        $response = $provider->chatWithFallback('test', [], ['llama2']);

        expect($response)->toBeArray()
            ->and($response)->toHaveKey('error');
    });

    test('getAvailableModels returns list of installed models', function () {
        $provider = new OllamaProvider;

        $models = $provider->getAvailableModels();

        expect($models)->toBeArray();

        // Check for qwen3 model (installed)
        $hasQwen = collect($models)->contains(fn ($m) => str_contains($m, 'qwen'));
        // Check for gemma3 as fallback (installed)
        $hasGemma = collect($models)->contains(fn ($m) => str_contains($m, 'gemma'));

        expect($hasQwen)->toBeTrue('Expected to find qwen model in: '.json_encode($models))
            ->and($hasGemma)->toBeTrue('Expected to find gemma model in: '.json_encode($models));
    })->group('live-api', 'ollama');

    test('getFallbackModels returns configured fallbacks', function () {
        $provider = new OllamaProvider;

        $fallbacks = $provider->getFallbackModels();

        expect($fallbacks)->toBeArray()
            ->and(count($fallbacks))->toBeGreaterThanOrEqual(1);
    });

    test('setFallbackModels changes fallback order', function () {
        $provider = new OllamaProvider;

        $provider->setFallbackModels(['llama2', 'gemma3']);

        expect($provider->getFallbackModels())->toBe(['llama2', 'gemma3']);
    });
});
