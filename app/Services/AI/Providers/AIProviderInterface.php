<?php

namespace App\Services\AI\Providers;

interface AIProviderInterface
{
    public function generateWithTools(array $conversation, array $tools): array;

    public function generate(string $systemPrompt, string $userMessage, array $tools = [], bool $useFallback = false): array;

    public function chat(string $message, array $context, bool $useFallback = false): array;
}
