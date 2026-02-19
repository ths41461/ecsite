<?php

namespace App\Services\AI;

class ResponseParser
{
    public function parseGeminiResponse(array $response): array
    {
        if (isset($response['promptFeedback']['blockReason'])) {
            return [
                'type' => 'blocked',
                'reason' => $response['promptFeedback']['blockReason'],
            ];
        }

        if (empty($response['candidates'])) {
            return ['type' => 'empty'];
        }

        $candidate = $response['candidates'][0] ?? null;
        if (! $candidate) {
            return ['type' => 'empty'];
        }

        $parts = $candidate['content']['parts'] ?? [];

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                return [
                    'type' => 'text',
                    'content' => $part['text'],
                ];
            }

            if (isset($part['functionCall'])) {
                return [
                    'type' => 'function_call',
                    'function_name' => $part['functionCall']['name'],
                    'function_args' => $part['functionCall']['args'] ?? [],
                ];
            }
        }

        return ['type' => 'empty'];
    }

    public function extractJsonFromMarkdown(string $text): ?array
    {
        if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
            $json = trim($matches[1]);
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        if (preg_match('/```\s*(.*?)\s*```/s', $text, $matches)) {
            $json = trim($matches[1]);
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return null;
    }

    public function parseRecommendationJson(string $json): array
    {
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in AI response: '.json_last_error_msg());
        }

        return $decoded;
    }

    public function parseError(array $errorResponse): array
    {
        $error = $errorResponse['error'] ?? $errorResponse;

        $code = $error['code'] ?? 500;
        $message = $error['message'] ?? 'Unknown error';
        $status = $error['status'] ?? 'UNKNOWN';

        return [
            'code' => $code,
            'message' => $message,
            'status' => $status,
            'is_rate_limit' => $code === 429 || str_contains($status, 'RESOURCE_EXHAUSTED'),
            'is_auth_error' => $code === 400 && str_contains($message, 'API key'),
            'is_server_error' => $code >= 500,
        ];
    }
}
