<?php

namespace App\Services\AI;

use App\Models\AiChatSession;
use App\Services\AI\Providers\AIProviderInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIRecommendationService
{
    protected AIProviderInterface $provider;

    protected ReActAgentEngine $agentEngine;

    protected ContextBuilder $contextBuilder;

    public function __construct(
        AIProviderInterface $provider,
        ReActAgentEngine $agentEngine,
        ContextBuilder $contextBuilder
    ) {
        $this->provider = $provider;
        $this->agentEngine = $agentEngine;
        $this->contextBuilder = $contextBuilder;
    }

    public function generateRecommendations(array $context): array
    {
        $cacheKey = $this->getCacheKey($context);

        return Cache::remember($cacheKey, 3600, function () use ($context) {
            try {
                return $this->agentEngine->execute($context, $this->provider);
            } catch (\Exception $e) {
                Log::warning('AI recommendation generation failed', [
                    'error' => $e->getMessage(),
                ]);

                return $this->getFallbackResponse($context);
            }
        });
    }

    public function chat(string $message, Collection $history, AiChatSession $session): array
    {
        $context = $this->contextBuilder->buildForChat($session, $history);

        try {
            $response = $this->provider->chat($message, $context);
        } catch (\Exception $e) {
            Log::warning('Chat failed, using fallback model', [
                'error' => $e->getMessage(),
            ]);
            $response = $this->provider->chat($message, $context, true);
        }

        return [
            'message' => $response['message'] ?? '',
            'products' => $response['products'] ?? [],
        ];
    }

    public function filterRecommendations(array $recommendations, array $filters): array
    {
        if (empty($filters)) {
            return $recommendations;
        }

        return collect($recommendations)->filter(function ($rec) use ($filters) {
            if (isset($filters['max_price']) && isset($rec['price'])) {
                if ($rec['price'] > $filters['max_price']) {
                    return false;
                }
            }

            if (isset($filters['min_price']) && isset($rec['price'])) {
                if ($rec['price'] < $filters['min_price']) {
                    return false;
                }
            }

            if (isset($filters['in_stock']) && $filters['in_stock']) {
                if (isset($rec['in_stock']) && ! $rec['in_stock']) {
                    return false;
                }
            }

            return true;
        })->values()->toArray();
    }

    public function getCacheKey(array $context): string
    {
        $relevantData = [
            'personality' => $context['user_profile']['personality'] ?? null,
            'vibe' => $context['user_profile']['vibe'] ?? null,
            'style' => $context['user_profile']['style'] ?? null,
            'budget' => $context['user_profile']['budget'] ?? null,
            'experience' => $context['user_profile']['experience'] ?? null,
        ];

        return 'ai_recommendations_'.md5(json_encode($relevantData));
    }

    protected function getFallbackResponse(array $context): array
    {
        return [
            'profile' => [
                'type' => 'balanced',
                'name' => 'バランス型',
                'description' => 'あなたの好みに合わせて、幅広い香りから選ぶことができます。',
            ],
            'recommendations' => [],
        ];
    }
}
