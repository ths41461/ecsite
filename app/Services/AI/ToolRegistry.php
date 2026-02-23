<?php

namespace App\Services\AI;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Log;

class ToolRegistry
{
    public function __construct() {}

    /**
     * Get all available tool definitions in Ollama format.
     */
    public function getTools(): array
    {
        return [
            $this->getSearchProductsTool(),
            $this->getCheckInventoryTool(),
            $this->getProductReviewsTool(),
        ];
    }

    /**
     * Execute a tool by name with given arguments.
     */
    public function execute(string $toolName, array $arguments): array
    {
        Log::info('ToolRegistry@execute', [
            'tool' => $toolName,
            'arguments' => $arguments,
        ]);

        return match ($toolName) {
            'search_products' => $this->executeSearchProducts($arguments),
            'check_inventory' => $this->executeCheckInventory($arguments),
            'get_product_reviews' => $this->executeGetProductReviews($arguments),
            default => [
                'success' => false,
                'error' => "Unknown tool: {$toolName}",
            ],
        };
    }

    /**
     * Get search_products tool definition.
     */
    private function getSearchProductsTool(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'search_products',
                'description' => '香水カタログから商品を検索します。カテゴリー、価格帯、ノート（香調）でフィルタリング可能です。',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => '検索クエリ（商品名、ブランド名など）',
                        ],
                        'category' => [
                            'type' => 'string',
                            'description' => 'カテゴリーでフィルタ（例: floral, woody, citrus）',
                        ],
                        'min_price' => [
                            'type' => 'integer',
                            'description' => '最低価格（円）',
                        ],
                        'max_price' => [
                            'type' => 'integer',
                            'description' => '最高価格（円）',
                        ],
                        'max_results' => [
                            'type' => 'integer',
                            'description' => '最大結果数',
                        ],
                    ],
                    'required' => [],
                ],
            ],
        ];
    }

    /**
     * Get check_inventory tool definition.
     */
    private function getCheckInventoryTool(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'check_inventory',
                'description' => '指定した商品の在庫状況を確認します。',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'product_ids' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                            'description' => '確認する商品バリアントIDのリスト',
                        ],
                    ],
                    'required' => ['product_ids'],
                ],
            ],
        ];
    }

    /**
     * Get get_product_reviews tool definition.
     */
    private function getProductReviewsTool(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'get_product_reviews',
                'description' => '商品のレビューと評価を取得します。',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'product_id' => [
                            'type' => 'integer',
                            'description' => '商品ID',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => '取得するレビュー数',
                        ],
                    ],
                    'required' => ['product_id'],
                ],
            ],
        ];
    }

    /**
     * Execute search_products tool.
     */
    private function executeSearchProducts(array $arguments): array
    {
        $query = $arguments['query'] ?? '';
        $category = $arguments['category'] ?? null;
        $minPrice = $arguments['min_price'] ?? null;
        $maxPrice = $arguments['max_price'] ?? null;
        $maxResults = $arguments['max_results'] ?? 10;

        $productsQuery = Product::query()
            ->where('is_active', true)
            ->with(['variants' => function ($q) {
                $q->where('is_active', true)->orderBy('price_yen', 'asc');
            }, 'brand', 'category']);

        if (! empty($query)) {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'like', "%{$query}%"));
            });
        }

        if ($category) {
            $productsQuery->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        if ($minPrice !== null || $maxPrice !== null) {
            $productsQuery->whereHas('variants', function ($q) use ($minPrice, $maxPrice) {
                if ($minPrice !== null) {
                    $q->where('price_yen', '>=', $minPrice);
                }
                if ($maxPrice !== null) {
                    $q->where('price_yen', '<=', $maxPrice);
                }
            });
        }

        $products = $productsQuery->limit($maxResults)->get();

        $results = $products->map(function ($product) {
            $minPriceVariant = $product->variants->first();
            $attributes = $product->attributes_json ?? [];

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'brand' => $product->brand?->name,
                'category' => $product->category?->name,
                'price' => $minPriceVariant?->price_yen,
                'notes' => $attributes['notes'] ?? [],
                'gender' => $attributes['gender'] ?? 'unisex',
            ];
        })->toArray();

        return [
            'success' => true,
            'products' => $results,
            'count' => count($results),
        ];
    }

    /**
     * Execute check_inventory tool.
     */
    private function executeCheckInventory(array $arguments): array
    {
        $productIds = $arguments['product_ids'] ?? [];

        if (empty($productIds)) {
            return [
                'success' => false,
                'error' => 'product_ids is required',
            ];
        }

        $inventory = Inventory::whereIn('product_variant_id', $productIds)
            ->with('variant.product')
            ->get();

        $results = $inventory->map(function ($inv) {
            return [
                'variant_id' => $inv->product_variant_id,
                'product_name' => $inv->variant?->product?->name,
                'stock' => $inv->stock,
                'safety_stock' => $inv->safety_stock,
                'available' => $inv->stock > $inv->safety_stock,
            ];
        })->toArray();

        return [
            'success' => true,
            'inventory' => $results,
        ];
    }

    /**
     * Execute get_product_reviews tool.
     */
    private function executeGetProductReviews(array $arguments): array
    {
        $productId = $arguments['product_id'] ?? null;
        $limit = $arguments['limit'] ?? 5;

        if (! $productId) {
            return [
                'success' => false,
                'error' => 'product_id is required',
            ];
        }

        $reviews = Review::where('product_id', $productId)
            ->where('approved', true)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $averageRating = Review::where('product_id', $productId)
            ->where('approved', true)
            ->avg('rating');

        $results = $reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'body' => $review->body,
                'user_name' => $review->user?->name ?? '匿名',
                'created_at' => $review->created_at->toIso8601String(),
            ];
        })->toArray();

        return [
            'success' => true,
            'reviews' => $results,
            'average_rating' => round($averageRating ?? 0, 1),
            'total_reviews' => Review::where('product_id', $productId)->where('approved', true)->count(),
        ];
    }
}
