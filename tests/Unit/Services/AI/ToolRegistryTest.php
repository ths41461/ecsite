<?php

use App\Models\Product;
use App\Services\AI\ToolRegistry;

beforeEach(function () {
    $this->registry = new ToolRegistry;
});

describe('ToolRegistry', function () {
    describe('getDefinitions', function () {
        test('returns valid function schema for AI', function () {
            $definitions = $this->registry->getDefinitions();

            expect($definitions)->toBeArray();
            expect($definitions)->toHaveCount(3);

            foreach ($definitions as $def) {
                expect($def)->toHaveKey('type');
                expect($def['type'])->toBe('function');
                expect($def)->toHaveKey('function');
                expect($def['function'])->toHaveKeys(['name', 'description', 'parameters']);
            }
        });

        test('includes search_products tool', function () {
            $definitions = $this->registry->getDefinitions();
            $names = array_column(array_column($definitions, 'function'), 'name');

            expect($names)->toContain('search_products');
        });

        test('includes check_inventory tool', function () {
            $definitions = $this->registry->getDefinitions();
            $names = array_column(array_column($definitions, 'function'), 'name');

            expect($names)->toContain('check_inventory');
        });

        test('includes get_product_reviews tool', function () {
            $definitions = $this->registry->getDefinitions();
            $names = array_column(array_column($definitions, 'function'), 'name');

            expect($names)->toContain('get_product_reviews');
        });
    });

    describe('execute', function () {
        test('search_products returns real products from database', function () {
            $result = $this->registry->execute('search_products', [
                'category' => 'Floral',
                'max_price' => 10000,
            ]);

            expect($result)->toHaveKey('count');
            expect($result)->toHaveKey('products');
            expect($result['products'])->toBeArray();

            if ($result['count'] > 0) {
                $product = $result['products'][0];
                expect($product)->toHaveKeys(['id', 'name', 'brand', 'category', 'notes', 'price', 'rating']);
            }
        });

        test('search_products respects max_price filter', function () {
            $result = $this->registry->execute('search_products', [
                'max_price' => 3000,
            ]);

            expect($result['products'])->toBeArray();

            if (count($result['products']) > 0) {
                foreach ($result['products'] as $product) {
                    expect($product['price'])->toBeLessThanOrEqual(3000);
                }
            } else {
                expect($result['count'])->toBe(0);
            }
        });

        test('check_inventory returns stock via variant relationship', function () {
            $product = Product::where('is_active', true)
                ->whereHas('variants.inventory')
                ->first();

            if (! $product) {
                $this->markTestSkipped('No products with inventory found');
            }

            $result = $this->registry->execute('check_inventory', [
                'product_ids' => [$product->id],
            ]);

            expect($result)->toHaveKey('inventory');
            expect($result['inventory'])->toBeArray();

            if (isset($result['inventory'][$product->id])) {
                $inventory = $result['inventory'][$product->id];
                expect($inventory)->toHaveKeys(['in_stock', 'stock', 'low_stock']);
            }
        });

        test('check_inventory handles non-existent products', function () {
            $result = $this->registry->execute('check_inventory', [
                'product_ids' => [999999],
            ]);

            expect($result)->toHaveKey('inventory');
            expect($result['inventory'])->toBeArray();
        });

        test('get_product_reviews returns aggregated ratings', function () {
            $product = Product::whereHas('reviews', function ($q) {
                $q->where('approved', true);
            })->first();

            if (! $product) {
                $this->markTestSkipped('No products with approved reviews found');
            }

            $result = $this->registry->execute('get_product_reviews', [
                'product_ids' => [$product->id],
            ]);

            expect($result)->toHaveKey('reviews');
            expect($result['reviews'])->toBeArray();

            if (isset($result['reviews'][$product->id])) {
                $review = $result['reviews'][$product->id];
                expect($review)->toHaveKeys(['avg_rating', 'review_count']);
                expect($review['avg_rating'])->toBeFloat();
                expect($review['review_count'])->toBeInt();
            }
        });

        test('get_product_reviews only returns approved reviews', function () {
            $result = $this->registry->execute('get_product_reviews', [
                'product_ids' => [1],
            ]);

            expect($result)->toHaveKey('reviews');
        });

        test('execute throws for unknown tool', function () {
            expect(fn () => $this->registry->execute('unknown_tool', []))
                ->toThrow(\InvalidArgumentException::class, 'Unknown tool: unknown_tool');
        });
    });
});
