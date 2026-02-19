<?php

namespace App\Services\AI;

use App\Models\AiChatSession;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;

class ContextBuilder
{
    public function build(array $quizData): array
    {
        return [
            'user_profile' => $this->buildUserProfile($quizData),
            'available_products' => $this->getAvailableProducts($quizData),
            'categories' => $this->getCategories(),
            'brands' => $this->getBrands(),
        ];
    }

    public function buildForChat(AiChatSession $session, Collection $history): array
    {
        $quizResult = $session->quizResult;

        if (! $quizResult) {
            return [
                'quiz_context' => [],
                'profile_type' => null,
                'previous_recommendations' => [],
                'chat_history' => $history->map(fn ($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ]),
                'budget' => 5000,
            ];
        }

        return [
            'quiz_context' => $quizResult->answers_json ?? [],
            'profile_type' => $quizResult->profile_type,
            'previous_recommendations' => $quizResult->recommended_product_ids ?? [],
            'chat_history' => $history->map(fn ($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ]),
            'budget' => $quizResult->answers_json['budget'] ?? 5000,
        ];
    }

    protected function buildUserProfile(array $quizData): array
    {
        return [
            'personality' => $quizData['personality'] ?? null,
            'vibe' => $quizData['vibe'] ?? null,
            'occasion' => $quizData['occasion'] ?? null,
            'style' => $quizData['style'] ?? null,
            'budget' => $quizData['budget'] ?? 5000,
            'experience' => $quizData['experience'] ?? null,
            'season' => $quizData['season'] ?? null,
        ];
    }

    protected function getAvailableProducts(array $quizData): array
    {
        $maxPrice = $quizData['budget'] ?? 5000;

        return Product::with(['variants', 'brand', 'category'])
            ->where('is_active', true)
            ->whereHas('variants', function ($q) use ($maxPrice) {
                $q->where('price_yen', '<=', $maxPrice)
                    ->where('is_active', true);
            })
            ->limit(50)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand?->name,
                'category' => $p->category?->name,
                'notes' => $p->attributes_json['notes'] ?? [],
                'gender' => $p->attributes_json['gender'] ?? 'unisex',
                'min_price' => $p->variants->where('is_active', true)->min('price_yen'),
                'max_price' => $p->variants->where('is_active', true)->max('price_yen'),
            ])
            ->filter(fn ($p) => $p['min_price'] <= $maxPrice)
            ->values()
            ->toArray();
    }

    protected function getCategories(): array
    {
        return Category::select('id', 'name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])
            ->toArray();
    }

    protected function getBrands(): array
    {
        return Brand::select('id', 'name')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
            ])
            ->toArray();
    }
}
