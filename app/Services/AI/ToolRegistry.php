<?php

namespace App\Services\AI;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;

class ToolRegistry
{
    public function getDefinitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_products',
                    'description' => 'Search fragrance products by category, price range, and notes. Returns matching products from catalog.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'category' => [
                                'type' => 'string',
                                'description' => 'Product category (e.g., floral, woody, fresh)',
                            ],
                            'max_price' => [
                                'type' => 'number',
                                'description' => 'Maximum price in yen',
                            ],
                            'notes' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                                'description' => 'Fragrance notes to search for',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'check_inventory',
                    'description' => 'Check stock levels for specific product IDs',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_ids' => [
                                'type' => 'array',
                                'items' => ['type' => 'integer'],
                                'description' => 'Array of product IDs to check',
                            ],
                        ],
                        'required' => ['product_ids'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_product_reviews',
                    'description' => 'Get reviews and ratings for products',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_ids' => [
                                'type' => 'array',
                                'items' => ['type' => 'integer'],
                                'description' => 'Array of product IDs',
                            ],
                        ],
                        'required' => ['product_ids'],
                    ],
                ],
            ],
        ];
    }

    public function execute(string $toolName, array $arguments): array
    {
        return match ($toolName) {
            'search_products' => $this->searchProducts($arguments),
            'check_inventory' => $this->checkInventory($arguments),
            'get_product_reviews' => $this->getProductReviews($arguments),
            default => throw new \InvalidArgumentException("Unknown tool: {$toolName}"),
        };
    }

    protected function searchProducts(array $args): array
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with(['variants.inventory', 'brand', 'category', 'reviews']);

        if (isset($args['category'])) {
            $query->whereHas('category', function ($q) use ($args) {
                $q->where('name', 'like', "%{$args['category']}%");
            });
        }

        if (isset($args['max_price'])) {
            $query->whereHas('variants', function ($q) use ($args) {
                $q->where('price_yen', '<=', $args['max_price'])
                    ->where('is_active', true);
            });
        }

        $products = $query->limit(20)->get();

        return [
            'count' => $products->count(),
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand?->name,
                'category' => $p->category?->name,
                'notes' => $p->attributes_json['notes'] ?? [],
                'gender' => $p->attributes_json['gender'] ?? 'unisex',
                'price' => $p->variants->where('is_active', true)->min('price_yen'),
                'rating' => $p->reviews->where('approved', true)->avg('rating'),
            ])->filter(fn ($p) => ! isset($args['max_price']) || $p['price'] <= $args['max_price'])->values()->toArray(),
        ];
    }

    protected function checkInventory(array $args): array
    {
        $productIds = $args['product_ids'] ?? [];

        if (empty($productIds)) {
            return ['inventory' => []];
        }

        $variantIds = ProductVariant::whereIn('product_id', $productIds)
            ->pluck('id');

        $inventories = Inventory::whereIn('product_variant_id', $variantIds)
            ->with('variant.product')
            ->get();

        $result = [];
        foreach ($inventories as $inv) {
            $productId = $inv->variant?->product_id;
            if ($productId && ! isset($result[$productId])) {
                $result[$productId] = [
                    'in_stock' => $inv->stock > $inv->safety_stock,
                    'stock' => $inv->stock,
                    'low_stock' => $inv->stock <= $inv->safety_stock && $inv->stock > 0,
                ];
            }
        }

        return ['inventory' => $result];
    }

    protected function getProductReviews(array $args): array
    {
        $productIds = $args['product_ids'] ?? [];

        if (empty($productIds)) {
            return ['reviews' => []];
        }

        $reviews = Review::whereIn('product_id', $productIds)
            ->where('approved', true)
            ->selectRaw('product_id, AVG(rating) as avg_rating, COUNT(*) as review_count')
            ->groupBy('product_id')
            ->get();

        $result = [];
        foreach ($reviews as $r) {
            $result[$r->product_id] = [
                'avg_rating' => round($r->avg_rating, 2),
                'review_count' => $r->review_count,
            ];
        }

        return ['reviews' => $result];
    }
}
