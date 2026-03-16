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
                'description' => '香水カタログから商品を検索します。カテゴリー、価格帯、ノート（香調）、性別でフィルタリング可能です。',
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
                        'gender' => [
                            'type' => 'string',
                            'description' => '性別でフィルタ（women, men, unisex）',
                        ],
                        'vibe' => [
                            'type' => 'string',
                            'description' => '香りのタイプでフィルタ（floral, citrus, vanilla, woody, ocean）',
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
                        'product_ids' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer'],
                            'description' => '特定の商品IDリスト（指定した場合、これらの商品を直接取得）',
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
        $gender = $arguments['gender'] ?? null;
        $vibe = $arguments['vibe'] ?? null;
        $minPrice = $arguments['min_price'] ?? null;
        $maxPrice = $arguments['max_price'] ?? null;
        $maxResults = $arguments['max_results'] ?? 10;
        $productIds = $arguments['product_ids'] ?? [];

        // If specific product IDs are provided, fetch those directly
        if (! empty($productIds)) {
            $products = Product::query()
                ->where('is_active', true)
                ->whereIn('id', $productIds)
                ->with(['variants' => function ($q) {
                    $q->where('is_active', true)->orderBy('price_yen', 'asc');
                }, 'brand', 'category', 'heroImage', 'images'])
                ->get();

            $results = $products->map(function ($product) {
                $minPriceVariant = $product->variants->first();
                $attributes = $product->attributes_json ?? [];

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
                        $options = $variant->option_json ?? [];

                        return [
                            'id' => $variant->id,
                            'sku' => $variant->sku,
                            'price_yen' => $variant->price_yen,
                            'sale_price_yen' => $variant->sale_price_yen,
                            'size_ml' => $options['size_ml'] ?? null,
                            'gender' => $options['gender'] ?? null,
                        ];
                    })->toArray(),
                    'attributes' => $attributes,
                ];
            })->toArray();

            return [
                'success' => true,
                'products' => $results,
                'count' => count($results),
            ];
        }

        $productsQuery = Product::query()
            ->where('is_active', true)
            ->with(['variants' => function ($q) {
                $q->where('is_active', true)->orderBy('price_yen', 'asc');
            }, 'brand', 'category', 'heroImage', 'images']);

        if (! empty($query)) {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'like', "%{$query}%"));
            });
        }

        if ($category) {
            $productsQuery->whereHas('category', fn ($q) => $q->where('slug', 'like', "%{$category}%"));
        }

        if ($gender) {
            $productsQuery->where(function ($q) use ($gender) {
                $q->whereJsonContains('attributes_json->gender', $gender)
                    ->orWhereJsonContains('attributes_json->gender', 'unisex');
            });
        }

        if ($vibe) {
            $vibeNoteMap = [
                'floral' => ['rose', 'jasmine', 'lily', 'peony', 'floral', '花', 'バラ', 'ジャスミン'],
                'citrus' => ['citrus', 'lemon', 'orange', 'bergamot', 'lime', 'シトラス', 'レモン'],
                'vanilla' => ['vanilla', 'sweet', 'amber', 'バニラ', '甘い'],
                'woody' => ['wood', 'sandalwood', 'cedar', 'patchouli', 'ウッディ', 'シダー'],
                'ocean' => ['ocean', 'marine', 'water', 'fresh', 'オーシャン', '海'],
            ];

            if (isset($vibeNoteMap[$vibe])) {
                $notes = $vibeNoteMap[$vibe];
                $productsQuery->where(function ($q) use ($notes) {
                    foreach ($notes as $note) {
                        $q->orWhereJsonContains('attributes_json->notes->top', $note)
                            ->orWhereJsonContains('attributes_json->notes->middle', $note)
                            ->orWhereJsonContains('attributes_json->notes->base', $note);
                    }
                });
            }
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
                'min_price' => $minPriceVariant?->price_yen,
                'short_desc' => $product->short_desc,
                'long_desc' => $product->long_desc,
                'notes' => $attributes['notes'] ?? [],
                'gender' => $attributes['gender'] ?? 'unisex',
                'image_url' => $product->heroImage?->path,
                'variants' => $product->variants->map(function ($variant) {
                    $options = $variant->option_json ?? [];

                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'price_yen' => $variant->price_yen,
                        'sale_price_yen' => $variant->sale_price_yen,
                        'size_ml' => $options['size_ml'] ?? null,
                        'gender' => $options['gender'] ?? null,
                    ];
                })->toArray(),
                'attributes' => $attributes,
            ];
        })->toArray();

        // #region agent log (sail-safe)
        try {
            $debugPath = base_path('.cursor/debug.log');
            @mkdir(dirname($debugPath), 0777, true);
            $sample = $results[0] ?? null;
            file_put_contents($debugPath, json_encode([
                'id' => uniqid('log_', true),
                'timestamp' => (int) round(microtime(true) * 1000),
                'runId' => 'pre',
                'hypothesisId' => 'H1',
                'location' => 'ToolRegistry.php:executeSearchProducts:results_sail',
                'message' => 'search_products returning products (sail-safe log)',
                'data' => [
                    'count' => count($results),
                    'sample_keys' => is_array($sample) ? array_keys($sample) : null,
                ],
            ], JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
        } catch (\Throwable $e) {
        }
        // #endregion

        // #region agent log
        try {
            $sample = $results[0] ?? null;
            file_put_contents('/code/ecsite/.cursor/debug.log', json_encode([
                'id' => uniqid('log_', true),
                'timestamp' => (int) round(microtime(true) * 1000),
                'runId' => 'pre',
                'hypothesisId' => 'H1',
                'location' => 'ToolRegistry.php:executeSearchProducts:results',
                'message' => 'search_products returning products',
                'data' => [
                    'count' => count($results),
                    'sample_keys' => is_array($sample) ? array_keys($sample) : null,
                    'sample_has_notes' => is_array($sample) ? array_key_exists('notes', $sample) : null,
                    'sample_notes_type' => is_array($sample) ? gettype($sample['notes'] ?? null) : null,
                ],
            ], JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
        } catch (\Throwable $e) {
        }
        // #endregion

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

        // Handle string input (some models pass JSON string)
        if (is_string($productIds)) {
            $decoded = json_decode($productIds, true);
            if (is_array($decoded)) {
                $productIds = $decoded;
            }
        }

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
