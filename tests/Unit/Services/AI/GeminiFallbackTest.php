<?php

use App\Services\AI\Providers\GeminiProvider;
use Illuminate\Support\Facades\Http;

describe('GeminiProvider Model Fallback', function () {
    beforeEach(function () {
        $this->provider = new GeminiProvider;
    });

    test('getApiUrl returns primary model by default', function () {
        $url = $this->provider->getApiUrl(false);

        expect($url)->toContain('gemini-2.5-flash-lite');
        expect($url)->not->toContain('gemini-2.5-flash"');
    });

    test('getApiUrl returns fallback model when requested', function () {
        $url = $this->provider->getApiUrl(true);

        expect($url)->toContain('gemini-2.5-flash');
        expect($url)->not->toContain('gemini-2.5-flash-lite');
    });

    test('sendRequest retries with fallback on rate limit error', function () {
        Http::fake([
            '*gemini-2.5-flash-lite*' => Http::response([
                'error' => [
                    'code' => 429,
                    'message' => 'Resource has been exhausted',
                    'status' => 'RESOURCE_EXHAUSTED',
                ],
            ], 429),
            '*gemini-2.5-flash*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [['text' => 'Fallback response']],
                            'role' => 'model',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->provider->generate('System', 'Hello', []);

        expect($response['type'])->toBe('text');
        expect($response['content'])->toBe('Fallback response');

        Http::assertSentCount(2);
    })->group('fallback');

    test('sendRequest throws when both models fail', function () {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'code' => 429,
                    'message' => 'Rate limited',
                    'status' => 'RESOURCE_EXHAUSTED',
                ],
            ], 429),
        ]);

        expect(fn () => $this->provider->generate('System', 'Hello', []))
            ->toThrow(\RuntimeException::class);

        Http::assertSentCount(2);
    })->group('fallback');

    test('sendRequest does not retry on non-rate-limit errors', function () {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'code' => 500,
                    'message' => 'Internal server error',
                    'status' => 'INTERNAL',
                ],
            ], 500),
        ]);

        expect(fn () => $this->provider->generate('System', 'Hello', []))
            ->toThrow(\RuntimeException::class);

        Http::assertSentCount(1);
    })->group('fallback');

    test('sendRequest does not retry fallback twice', function () {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'code' => 429,
                    'message' => 'Rate limited',
                    'status' => 'RESOURCE_EXHAUSTED',
                ],
            ], 429),
        ]);

        expect(fn () => $this->provider->generate('System', 'Hello', []))
            ->toThrow(\RuntimeException::class);

        Http::assertSentCount(2);
    })->group('fallback');
});
