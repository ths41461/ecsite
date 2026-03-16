<?php

namespace App\Services\AI;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;

class ContextBuilder
{
    private int $maxProducts;

    private int $maxTrending;

    private int $maxTopRated;

    private array $config;

    public function __construct()
    {
        $this->maxProducts = (int) config('ai.context.max_products', 20);
        $this->maxTrending = (int) config('ai.context.max_trending', 5);
        $this->maxTopRated = (int) config('ai.context.max_top_rated', 5);
        $this->config = config('fragrance', []);
    }

    /**
     * Build context array from quiz data for AI consumption.
     */
    public function build(array $quizData): array
    {
        Log::info('ContextBuilder@build - Starting context build', [
            'quiz_data' => $quizData,
        ]);

        $userProfile = $this->buildUserProfile($quizData);

        // Get products filtered by vibe (notes), personality, occasion, and budget
        $availableProducts = $this->getMatchedProducts($quizData);

        // Fallback to simple budget filter if no matches
        if (empty($availableProducts)) {
            $availableProducts = $this->getAvailableProducts($quizData);
        }

        $trendingProducts = $this->getTrendingProducts();
        $topRatedProducts = $this->getTopRatedProducts();

        // Build detailed database info for AI
        $databaseInfo = $this->getDatabaseSummary();

        $context = [
            'user_profile' => $userProfile,
            'budget' => $quizData['budget'] ?? 10000,
            'available_products' => $availableProducts,
            'trending_products' => $trendingProducts,
            'top_rated_products' => $topRatedProducts,
            'database_info' => $databaseInfo,
            'matching_criteria' => $this->buildMatchingCriteria($quizData),
        ];

        Log::info('ContextBuilder@build - Context built successfully', [
            'products_count' => count($availableProducts),
            'trending_count' => count($trendingProducts),
            'top_rated_count' => count($topRatedProducts),
        ]);

        return $context;
    }

    /**
     * Build user profile from quiz data with full details.
     */
    private function buildUserProfile(array $quizData): array
    {
        $personality = $quizData['personality'] ?? null;
        $vibe = $quizData['vibe'] ?? null;

        // Get personality mapping
        $personalityMapping = $this->getPersonalityMapping($personality);

        // Get vibe mapping
        $vibeMapping = $this->getVibeMapping($vibe);

        return [
            'personality' => $personality,
            'vibe' => $vibe,
            'occasion' => $quizData['occasion'] ?? [],
            'style' => $quizData['style'] ?? null,
            'experience' => $quizData['experience'] ?? null,
            'season' => $quizData['season'] ?? null,
            'gender' => $quizData['gender'] ?? 'unisex',
            'budget' => $quizData['budget'] ?? 10000,
            'concentration' => $quizData['concentration'] ?? null,
            // Additional mappings for AI
            'personality_details' => $personalityMapping,
            'vibe_details' => $vibeMapping,
        ];
    }

    /**
     * Get personality mapping with recommended brands/styles.
     */
    private function getPersonalityMapping(?string $personality): array
    {
        $mappings = $this->config['personality_styles'] ?? [];

        if (! $personality || ! isset($mappings[$personality])) {
            return [
                'styles' => [],
                'genders' => ['women', 'men', 'unisex'],
                'brands' => [],
                'keywords' => [],
            ];
        }

        return $mappings[$personality];
    }

    /**
     * Get vibe mapping with fragrance notes.
     */
    private function getVibeMapping(?string $vibe): array
    {
        $vibeNotes = $this->config['vibe_notes'] ?? [];
        $vibeCategories = $this->config['vibe_categories'] ?? [];

        if (! $vibe) {
            return [
                'notes' => [],
                'categories' => [],
                'keywords' => [],
            ];
        }

        $notes = $vibeNotes[$vibe] ?? [];
        $categories = $vibeCategories[$vibe] ?? [];

        // Flatten keywords from notes
        $keywords = [];
        foreach ($notes as $noteType => $noteList) {
            $keywords = array_merge($keywords, $noteList);
        }

        return [
            'notes' => $notes,
            'categories' => $categories,
            'keywords' => $keywords,
            'vibe_name' => $this->getVibeJapaneseName($vibe),
        ];
    }

    /**
     * Get Japanese name for vibe.
     */
    private function getVibeJapaneseName(string $vibe): string
    {
        $names = [
            'floral' => 'フローラル',
            'citrus' => 'シトラス',
            'vanilla' => 'スイート',
            'woody' => 'ウッディ',
            'ocean' => 'オーシャン',
        ];

        return $names[$vibe] ?? $vibe;
    }

    /**
     * Get products matched by vibe, personality, occasion, and budget.
     */
    private function getMatchedProducts(array $quizData): array
    {
        $budget = $quizData['budget'] ?? 10000;
        $vibe = $quizData['vibe'] ?? null;
        $personality = $quizData['personality'] ?? null;
        $gender = $quizData['gender'] ?? 'unisex';
        $occasion = $quizData['occasion'] ?? [];
        $experience = $quizData['experience'] ?? null;

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
            }, 'brand', 'category', 'heroImage', 'images']);

        // Filter by vibe (notes matching)
        if ($vibe) {
            $query = $this->applyVibeFilter($query, $vibe);
        }

        // Filter by gender - check both product attributes_json and variant option_json
        if ($gender && $gender !== 'unisex') {
            $query->where(function ($q) use ($gender) {
                // Check product-level attributes_json.gender
                $q->where('attributes_json->gender', $gender)
                    // OR check variant-level option_json.gender
                    ->orWhereHas('variants', function ($q2) use ($gender) {
                        $q2->where('option_json->gender', $gender);
                    });
            });
        }

        // Filter by personality (brand preferences)
        if ($personality) {
            $query = $this->applyPersonalityFilter($query, $personality);
        }

        $products = $query->limit($this->maxProducts)->get();

        // Score and sort by match quality
        $products = $this->scoreAndSortProducts($products, $quizData);

        return $products->map(function ($product) {
            return $this->formatProduct($product);
        })->toArray();
    }

    /**
     * Apply vibe/notes filter to query.
     */
    private function applyVibeFilter($query, string $vibe)
    {
        $vibeNotes = $this->config['vibe_notes'] ?? [];

        if (! isset($vibeNotes[$vibe])) {
            return $query;
        }

        $notes = $vibeNotes[$vibe];
        $searchNotes = [];

        // Collect all notes from top/middle/base
        foreach ($notes as $noteType => $noteList) {
            foreach ($noteList as $note) {
                $searchNotes[] = $note;
                // Also search Japanese
                $searchNotes[] = $this->translateNoteToJapanese($note);
            }
        }

        // Use whereRaw to search in JSON
        $searchNotes = array_unique($searchNotes);

        return $query->where(function ($q) use ($searchNotes) {
            foreach ($searchNotes as $note) {
                $q->orWhere('attributes_json', 'LIKE', "%{$note}%");
            }
        });
    }

    /**
     * Apply personality filter (brand preferences).
     */
    private function applyPersonalityFilter($query, string $personality)
    {
        $personalityMapping = $this->config['personality_styles'] ?? [];

        if (! isset($personalityMapping[$personality])) {
            return $query;
        }

        $preferredBrands = $personalityMapping[$personality]['brands'] ?? [];

        if (! empty($preferredBrands)) {
            $brandIds = Brand::whereIn('name', $preferredBrands)->pluck('id')->toArray();

            if (! empty($brandIds)) {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        return $query;
    }

    /**
     * Translate note to Japanese for search.
     */
    private function translateNoteToJapanese(string $note): string
    {
        $translations = [
            'Bergamot' => 'ベルガamot',
            'Lemon' => 'レモン',
            'Orange' => 'オレンジ',
            'Rose' => 'ローズ',
            'Jasmine' => 'ジャスミン',
            'Musk' => 'ムスク',
            'Vanilla' => 'バニラ',
            'Sandalwood' => 'サンダルウッド',
            'Cedar' => 'シダー',
            'Vetiver' => 'ベチバー',
            'Marine' => 'マリン',
            'Water' => 'ウォーター',
            'Mint' => 'ミント',
            'Peony' => 'ピオニー',
            'Freesia' => 'フリージア',
        ];

        return $translations[$note] ?? $note;
    }

    /**
     * Score products by match quality and sort.
     */
    private function scoreAndSortProducts($products, array $quizData): \Illuminate\Support\Collection
    {
        return $products->map(function ($product) use ($quizData) {
            $score = 0;

            // Score by vibe match (highest weight)
            if (isset($quizData['vibe'])) {
                $score += $this->calculateVibeScore($product, $quizData['vibe']) * 30;
            }

            // Score by personality/brand match
            if (isset($quizData['personality'])) {
                $score += $this->calculatePersonalityScore($product, $quizData['personality']) * 20;
            }

            // Score by price (prefer middle of budget)
            $minPrice = $product->variants->min('price_yen');
            $budget = $quizData['budget'] ?? 10000;
            if ($minPrice <= $budget * 0.7 && $minPrice >= $budget * 0.3) {
                $score += 15; // Sweet spot
            } elseif ($minPrice <= $budget) {
                $score += 5;
            }

            // Score by gender match
            $productGender = $product->attributes_json['gender'] ?? 'unisex';
            $quizGender = $quizData['gender'] ?? 'unisex';
            if ($productGender === $quizGender || $productGender === 'unisex' || $quizGender === 'unisex') {
                $score += 10;
            }

            // Bonus for featured
            if ($product->featured) {
                $score += 5;
            }

            return ['product' => $product, 'score' => $score];
        })
            ->sortByDesc('score')
            ->pluck('product');
    }

    /**
     * Calculate vibe match score.
     */
    private function calculateVibeScore(Product $product, string $vibe): float
    {
        $vibeNotes = $this->config['vibe_notes'] ?? [];

        if (! isset($vibeNotes[$vibe])) {
            return 0;
        }

        $productNotes = $product->attributes_json['notes'] ?? [];
        $allVibeNotes = [];

        foreach ($vibeNotes[$vibe] as $noteType => $notes) {
            $allVibeNotes = array_merge($allVibeNotes, $notes);
        }

        $matchCount = 0;

        // Check top notes
        $topNotes = is_array($productNotes['top'] ?? '')
            ? $productNotes['top']
            : ($productNotes['top'] ? explode('、', $productNotes['top']) : []);

        // Check middle notes
        $middleNotes = is_array($productNotes['middle'] ?? '')
            ? $productNotes['middle']
            : ($productNotes['middle'] ? explode('、', $productNotes['middle']) : []);

        // Check base notes
        $baseNotes = is_array($productNotes['base'] ?? '')
            ? $productNotes['base']
            : ($productNotes['base'] ? explode('、', $productNotes['base']) : []);

        $allProductNotes = array_merge($topNotes, $middleNotes, $baseNotes);

        foreach ($allProductNotes as $note) {
            foreach ($allVibeNotes as $vibeNote) {
                if (stripos($note, $vibeNote) !== false || stripos($vibeNote, $note) !== false) {
                    $matchCount++;
                }
            }
        }

        return min(1.0, $matchCount / 3); // Max score at 3 matches
    }

    /**
     * Calculate personality/brand match score.
     */
    private function calculatePersonalityScore(Product $product, string $personality): float
    {
        $personalityMapping = $this->config['personality_styles'] ?? [];

        if (! isset($personalityMapping[$personality])) {
            return 0;
        }

        $preferredBrands = $personalityMapping[$personality]['brands'] ?? [];
        $brandName = $product->brand?->name ?? '';

        foreach ($preferredBrands as $brand) {
            if (stripos($brandName, $brand) !== false) {
                return 1.0;
            }
        }

        return 0;
    }

    /**
     * Get available products within budget (fallback method).
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
            }, 'brand', 'category', 'heroImage', 'images']);

        $products = $query->limit($this->maxProducts)->get();

        return $products->map(function ($product) {
            return $this->formatProduct($product);
        })->toArray();
    }

    /**
     * Format product for AI context.
     */
    private function formatProduct($product): array
    {
        $minPriceVariant = $product->variants->first();
        $attributes = $product->attributes_json ?? [];
        $options = $minPriceVariant?->option_json ?? [];

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'brand' => $product->brand?->name,
            'category' => $product->category?->name,
            'min_price' => $minPriceVariant?->price_yen,
            'short_desc' => $product->short_desc,
            'long_desc' => $product->long_desc,
            'notes' => $attributes['notes'] ?? [],
            'gender' => $attributes['gender'] ?? 'unisex',
            'image_url' => $product->heroImage?->path,
            'variants' => $product->variants->map(function ($variant) {
                $opts = $variant->option_json ?? [];

                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price_yen' => $variant->price_yen,
                    'sale_price_yen' => $variant->sale_price_yen,
                    'size_ml' => $opts['size_ml'] ?? null,
                    'gender' => $opts['gender'] ?? null,
                    'concentration' => $opts['concentration'] ?? null,
                ];
            })->toArray(),
            'attributes' => $attributes,
            'featured' => $product->featured,
        ];
    }

    /**
     * Get trending products.
     */
    private function getTrendingProducts(): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->where('featured', true)
            ->with(['variants' => function ($q) {
                $q->where('is_active', true)->orderBy('price_yen', 'asc');
            }, 'brand', 'heroImage', 'images'])
            ->limit($this->maxTrending)
            ->get();

        return $products->map(function ($product) {
            return $this->formatProduct($product);
        })->toArray();
    }

    /**
     * Get top rated products.
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
            }, 'brand', 'heroImage', 'images'])
            ->orderByDesc('avg_rating')
            ->limit($this->maxTopRated)
            ->get();

        return $products->map(function ($product) {
            $formatted = $this->formatProduct($product);
            $formatted['avg_rating'] = round($product->avg_rating ?? 0, 1);

            return $formatted;
        })->toArray();
    }

    /**
     * Get database summary for AI context.
     */
    private function getDatabaseSummary(): array
    {
        return [
            'total_products' => Product::where('is_active', true)->count(),
            'total_brands' => Brand::count(),
            'total_categories' => Category::count(),
            'brands' => Brand::pluck('name')->toArray(),
            'categories' => Category::whereNotNull('parent_id')->pluck('name')->toArray(),
            'price_range' => [
                'min' => ProductVariant::where('is_active', true)->min('price_yen'),
                'max' => ProductVariant::where('is_active', true)->max('price_yen'),
            ],
            'concentrations' => ['EDP', 'EDT', 'EDC', 'Mist', 'Oil', 'Parfum'],
            'genders' => ['women', 'men', 'unisex'],
        ];
    }

    /**
     * Build matching criteria for AI prompt.
     */
    private function buildMatchingCriteria(array $quizData): string
    {
        $criteria = [];

        if ($vibe = $quizData['vibe'] ?? null) {
            $vibeName = $this->getVibeJapaneseName($vibe);
            $criteria[] = "好みの香り: {$vibeName}";
        }

        if ($personality = $quizData['personality'] ?? null) {
            $criteria[] = "性格タイプ: {$personality}";
        }

        if ($budget = $quizData['budget'] ?? null) {
            $criteria[] = "予算: {$budget}円";
        }

        if ($gender = $quizData['gender'] ?? null) {
            $genderJa = $gender === 'women' ? '女性' : ($gender === 'men' ? '男性' : 'ユニセックス');
            $criteria[] = "性別: {$genderJa}";
        }

        if (! empty($quizData['occasion'])) {
            $criteria[] = '使用シーン: '.implode('、', $quizData['occasion']);
        }

        return implode(' / ', $criteria);
    }
}
