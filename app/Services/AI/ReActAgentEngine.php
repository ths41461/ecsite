<?php

namespace App\Services\AI;

use App\Services\AI\Providers\AIProviderInterface;

class ReActAgentEngine
{
    protected ToolRegistry $toolRegistry;

    protected int $maxIterations;

    public function __construct(ToolRegistry $toolRegistry, int $maxIterations = 5)
    {
        $this->toolRegistry = $toolRegistry;
        $this->maxIterations = $maxIterations;
    }

    public function getMaxIterations(): int
    {
        return $this->maxIterations;
    }

    public function execute(array $context, AIProviderInterface $provider): array
    {
        $iteration = 0;
        $conversation = [];

        $systemPrompt = $this->buildSystemPrompt($context);
        $conversation[] = ['role' => 'system', 'content' => $systemPrompt];

        while ($iteration < $this->maxIterations) {
            $iteration++;

            $tools = $this->toolRegistry->getDefinitions();
            $response = $provider->generateWithTools($conversation, $tools);

            if ($response['type'] === 'text') {
                return $this->parseFinalResponse($response['content']);
            }

            if ($response['type'] === 'function_call') {
                $toolCall = [
                    'function' => [
                        'name' => $response['function_name'],
                        'arguments' => json_encode($response['function_args']),
                    ],
                ];

                $conversation[] = [
                    'role' => 'assistant',
                    'tool_calls' => [$toolCall],
                ];

                try {
                    $toolResult = $this->executeTool($toolCall);
                } catch (\Exception $e) {
                    $toolResult = ['error' => $e->getMessage()];
                }

                $conversation[] = [
                    'role' => 'tool',
                    'tool_name' => $response['function_name'],
                    'content' => json_encode($toolResult),
                ];

                continue;
            }

            throw new \RuntimeException('Unexpected AI response type: '.$response['type']);
        }

        throw new \RuntimeException('Max iterations reached without final answer');
    }

    public function buildSystemPrompt(array $context): string
    {
        $profile = $context['user_profile'] ?? [];
        $personality = $profile['personality'] ?? 'unknown';
        $vibe = $profile['vibe'] ?? 'unknown';
        $budget = $profile['budget'] ?? 5000;
        $style = $profile['style'] ?? 'unknown';
        $experience = $profile['experience'] ?? 'beginner';

        $toolDescriptions = collect($this->toolRegistry->getDefinitions())
            ->map(fn ($t) => "- {$t['function']['name']}: {$t['function']['description']}")
            ->join("\n");

        return <<<PROMPT
You are an expert fragrance consultant helping users find their perfect perfume.

USER CONTEXT:
- Personality: {$personality}
- Preferred Vibe: {$vibe}
- Style: {$style}
- Budget: Under ¥{$budget}
- Experience Level: {$experience}

Follow the ReAct pattern:
1. THINK: Analyze what information you need
2. ACT: Call appropriate tools to gather data
3. OBSERVE: Review tool results
4. REPEAT: Until you have enough information
5. FINAL: Provide recommendations in JSON format

You have access to these tools:
{$toolDescriptions}

Return your final answer as JSON with this structure:
{
  "profile": {
    "type": "string",
    "name": "string in Japanese",
    "description": "string in Japanese"
  },
  "recommendations": [
    {
      "product_id": number,
      "match_score": number,
      "explanation": "string in Japanese"
    }
  ]
}

IMPORTANT: Only call tools when you need more information. If you already have enough context from the user profile, provide recommendations directly.
PROMPT;
    }

    public function executeTool(array $toolCall): array
    {
        $toolName = $toolCall['function']['name'] ?? '';
        $argumentsJson = $toolCall['function']['arguments'] ?? '{}';

        $arguments = json_decode($argumentsJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in tool arguments: '.json_last_error_msg());
        }

        return $this->toolRegistry->execute($toolName, $arguments);
    }

    public function parseFinalResponse(string $content): array
    {
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $content = trim($content);

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in AI response: '.json_last_error_msg());
        }

        return $data;
    }
}
