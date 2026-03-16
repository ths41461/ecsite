<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class ResponseParser
{
    public function __construct() {}

    /**
     * Parse Ollama chat API response.
     */
    public function parseOllamaChatResponse(array $response): array
    {
        $message = $response['message']['content'] ?? '';
        $toolCalls = [];

        if (isset($response['message']['tool_calls'])) {
            foreach ($response['message']['tool_calls'] as $toolCall) {
                $toolCalls[] = [
                    'function' => $toolCall['function']['name'] ?? '',
                    'arguments' => $this->parseToolCallArguments($toolCall),
                ];
            }
        }

        return [
            'message' => $message,
            'model' => $response['model'] ?? 'unknown',
            'done' => $response['done'] ?? true,
            'tool_calls' => $toolCalls,
        ];
    }

    /**
     * Parse tool call arguments (handles both JSON string and array).
     */
    public function parseToolCallArguments(array $toolCall): array
    {
        $arguments = $toolCall['function']['arguments'] ?? [];

        if (is_string($arguments)) {
            $decoded = json_decode($arguments, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            Log::warning('ResponseParser: Failed to decode tool arguments', [
                'arguments' => $arguments,
                'error' => json_last_error_msg(),
            ]);

            return [];
        }

        if (is_array($arguments)) {
            return $arguments;
        }

        return [];
    }

    /**
     * Extract product IDs mentioned in text.
     *
     * @param  int  $maxProductId  Maximum valid product ID
     */
    public function extractProductIdsFromText(string $text, int $maxProductId = 10000): array
    {
        preg_match_all('/\b(\d+)\b/', $text, $matches);

        $ids = [];
        foreach ($matches[1] as $match) {
            $id = (int) $match;
            if ($id > 0 && $id <= $maxProductId && ! in_array($id, $ids)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * Build final response for API output.
     */
    public function buildFinalResponse(string $aiMessage, array $products, array $profile): array
    {
        return [
            'message' => $aiMessage,
            'products' => $products,
            'profile' => $profile,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Parse Ollama streaming response chunk.
     */
    public function parseOllamaStreamingResponse(array $chunk): array
    {
        $toolCalls = [];

        if (isset($chunk['message']['tool_calls'])) {
            foreach ($chunk['message']['tool_calls'] as $toolCall) {
                $toolCalls[] = [
                    'function' => $toolCall['function']['name'] ?? '',
                    'arguments' => $this->parseToolCallArguments($toolCall),
                ];
            }
        }

        return [
            'content' => $chunk['message']['content'] ?? '',
            'done' => $chunk['done'] ?? false,
            'model' => $chunk['model'] ?? 'unknown',
            'tool_calls' => $toolCalls,
        ];
    }
}
