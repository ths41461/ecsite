<?php

namespace App\Services\AI\Providers;

class MockGeminiProvider implements AIProviderInterface
{
    protected array $mockResponses = [];

    protected int $callCount = 0;

    protected bool $shouldReturnFunctionCall = false;

    public function setMockResponse(array $response): self
    {
        $this->mockResponses[] = $response;

        return $this;
    }

    public function setShouldReturnFunctionCall(bool $value): self
    {
        $this->shouldReturnFunctionCall = $value;

        return $this;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }

    public function generateWithTools(array $conversation, array $tools): array
    {
        $this->callCount++;

        if ($this->shouldReturnFunctionCall) {
            return [
                'type' => 'function_call',
                'function_name' => 'search_products',
                'function_args' => ['category' => 'floral', 'max_price' => 5000],
            ];
        }

        if (! empty($this->mockResponses)) {
            return array_shift($this->mockResponses);
        }

        return [
            'type' => 'text',
            'content' => json_encode([
                'profile' => [
                    'type' => 'fresh_girly',
                    'name' => 'フレッシュ＆ガーリー',
                    'description' => '明るく元気なあなたにぴったりのフレッシュな香り',
                ],
                'recommendations' => [
                    ['product_id' => 1, 'match_score' => 95, 'explanation' => 'とても相性が良いです'],
                    ['product_id' => 2, 'match_score' => 90, 'explanation' => 'おすすめです'],
                ],
            ]),
        ];
    }

    public function generate(string $systemPrompt, string $userMessage, array $tools = [], bool $useFallback = false): array
    {
        $this->callCount++;

        if ($this->shouldReturnFunctionCall && ! empty($tools)) {
            return [
                'type' => 'function_call',
                'function_name' => 'search_products',
                'function_args' => ['category' => 'floral', 'max_price' => 5000],
            ];
        }

        if (! empty($this->mockResponses)) {
            return array_shift($this->mockResponses);
        }

        return [
            'type' => 'text',
            'content' => 'This is a mock response from the AI.',
        ];
    }

    public function chat(string $message, array $context, bool $useFallback = false): array
    {
        $this->callCount++;

        if (! empty($this->mockResponses)) {
            return array_shift($this->mockResponses);
        }

        return [
            'message' => 'こんにちは！フレッシュな香りのおすすめをご紹介しますね。',
            'products' => [],
        ];
    }

    public function reset(): void
    {
        $this->mockResponses = [];
        $this->callCount = 0;
        $this->shouldReturnFunctionCall = false;
    }
}
