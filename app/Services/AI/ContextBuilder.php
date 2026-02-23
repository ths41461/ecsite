<?php

namespace App\Services\AI;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ContextBuilder
{
    private int $maxProducts;

    private int $maxTrending;

    private int $maxTopRated;

    public function __construct()
    {
        $this->maxProducts = (int) config('ai.context.max_products', 20);
        $this->maxTrending = (int) config('ai.context.max_trending', 5);
        $this->maxTopRated = (int) config('ai.context.max_top_rated', 5);
    }

    /**
     * Build context array from quiz data for AI consumption.
     *
     * @param  array  $quizData  Quiz submission data
     * @return array Context for AI provider
     */
    public function build(array $quizData): array
    {
        Log::info('ContextBuilder@build - Starting context build', [
            'quiz_data' => $quizData,
        ]);

        $userProfile = $this->buildUserProfile($quizData);
        $availableProducts = $this->getAvailableProducts($quizData);
        $trendingProducts = $this->getTrendingProducts();
        $topRatedProducts = $this->getTopRatedProducts();

        $context = [
            'user_profile' => $userProfile,
            'budget' => $quizData['budget'] ?? 10000,
            'available_products' => $availableProducts,
            'trending_products' => $trendingProducts,
            'top_rated_products' => $topRatedProducts,
        ];

        Log::info('ContextBuilder@build - Context built successfully', [
            'products_count' => count($availableProducts),
            'trending_count' => count($trendingProducts),
            'top_rated_count' => count($topRatedProducts),
        ]);

        return $context;
    }

    /**
     * Build user profile from quiz data.
     */
    private function buildUserProfile(array $quizData): array
    {
        return [
            'personality' => $quizData['personality'] ?? null,
            'vibe' => $quizData['vibe'] ?? null,
            'occasion' => $quizData['occasion'] ?? [],
            'style' => $quizData['style'] ?? null,
            'experience' => $quizData['experience'] ?? null,
            'season' => $quizData['season'] ?? null,
            'gender' => $quizData['gender'] ?? 'unisex',
            'budget' => $quizData['budget'] ?? 10000,
        ];
    }

    /**
     * Get available products within budget, optionally filtered by gender.
     */
    private function getAvailableProducts(array $quizData): array
    {
        $budget = $quizData['budget'] ?? 10000;
        $gender = $quizData['gender'] ?? null;

        $query = Product::query()
            ->where('is_active', true)
            ->whereHas('variants', function ($q) use ($budget) {
                $q->where('is_active', true)
                    ->where('price_yen', '<=', $budget);
            })
            ->with(['variants' => function ($q) use ($budget) {
                $q->where('is_active', true)
                    ->where('price_yen', '<=', $budget)
                    ->orderBy('price_yen', 'asc');
            }, 'brand', 'category']);

        $products = $query->limit($this->maxProducts)->get();

        return $products->map(function ($product) {
            $minPriceVariant = $product->variants->first();
            $attributes = $product->attributes_json ?? [];

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'brand' => $product->brand?->name,
                'category' => $product->category?->name,
                'min_price' => $minPriceVariant?->price_yen,
                'notes' => $attributes['notes'] ?? [],
                'gender' => $attributes['gender'] ?? 'unisex',
            ];
        })->toArray();
    }

    /**
     * Get trending products (featured products).
     */
    private function getTrendingProducts(): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->where('featured', true)
            ->with(['variants' => function ($q) {
                $q->where('is_active', true)->orderBy('price_yen', 'asc');
            }, 'brand'])
            ->limit($this->maxTrending)
            ->get();

        return $products->map(function ($product) {
            $minPriceVariant = $product->variants->first();
            $attributes = $product->attributes_json ?? [];

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'brand' => $product->brand?->name,
                'min_price' => $minPriceVariant?->price_yen,
                'notes' => $attributes['notes'] ?? [],
            ];
        })->toArray();
    }

    /**
     * Get top rated products based on reviews.
     */
    private function getTopRatedProducts(): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->whereHas('reviews', function ($q) {
                $q->where('approved', true);
            })
            ->withAvg('reviews as avg_rating', 'rating')
            ->with(['variants' => function ($q) {
                $q->where('is_active', true)->orderBy('price_yen', 'asc');
            }, 'brand'])
            ->orderByDesc('avg_rating')
            ->limit($this->maxTopRated)
            ->get();

        return $products->map(function ($product) {
            $minPriceVariant = $product->variants->first();
            $attributes = $product->attributes_json ?? [];

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'brand' => $product->brand?->name,
                'min_price' => $minPriceVariant?->price_yen,
                'notes' => $attributes['notes'] ?? [],
                'avg_rating' => round($product->avg_rating ?? 0, 1),
            ];
        })->toArray();
    }
}
