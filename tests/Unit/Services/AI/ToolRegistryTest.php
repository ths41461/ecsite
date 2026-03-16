<?php

/**
 * @group unit
 * @group ai
 */

use App\Models\Product;
use App\Services\AI\ToolRegistry;

describe('ToolRegistry', function () {

    test('can instantiate tool registry', function () {
        $registry = new ToolRegistry;

        expect($registry)->toBeInstanceOf(ToolRegistry::class);
    });

    test('getTools returns array of tool definitions', function () {
        $registry = new ToolRegistry;
        $tools = $registry->getTools();

        expect($tools)->toBeArray()
            ->and(count($tools))->toBeGreaterThan(0);
    });

    test('tools have required Ollama format structure', function () {
        $registry = new ToolRegistry;
        $tools = $registry->getTools();

        foreach ($tools as $tool) {
            expect($tool)->toHaveKey('type')
                ->and($tool['type'])->toBe('function')
                ->and($tool)->toHaveKey('function')
                ->and($tool['function'])->toHaveKeys(['name', 'description', 'parameters']);
        }
    });

    test('has search_products tool', function () {
        $registry = new ToolRegistry;
        $tools = $registry->getTools();

        $searchTool = collect($tools)->firstWhere('function.name', 'search_products');

        expect($searchTool)->not->toBeNull()
            ->and($searchTool['function']['description'])->toBeString()
            ->and($searchTool['function']['parameters'])->toHaveKey('properties');
    });

    test('has check_inventory tool', function () {
        $registry = new ToolRegistry;
        $tools = $registry->getTools();

        $inventoryTool = collect($tools)->firstWhere('function.name', 'check_inventory');

        expect($inventoryTool)->not->toBeNull()
            ->and($inventoryTool['function']['description'])->toBeString();
    });

    test('has get_product_reviews tool', function () {
        $registry = new ToolRegistry;
        $tools = $registry->getTools();

        $reviewsTool = collect($tools)->firstWhere('function.name', 'get_product_reviews');

        expect($reviewsTool)->not->toBeNull()
            ->and($reviewsTool['function']['description'])->toBeString();
    });

    test('execute returns real product search results', function () {
        $registry = new ToolRegistry;

        $result = $registry->execute('search_products', [
            'query' => 'floral',
            'max_results' => 5,
        ]);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('success')
            ->and($result)->toHaveKey('products');
    });

    test('execute search_products returns real products from database', function () {
        $registry = new ToolRegistry;

        $result = $registry->execute('search_products', [
            'query' => '',
            'max_results' => 10,
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['products'])->toBeArray();

        if (count($result['products']) > 0) {
            $product = $result['products'][0];
            expect($product)->toHaveKeys(['id', 'name', 'slug', 'brand', 'price']);
        }
    });

    test('execute check_inventory returns real stock data', function () {
        $registry = new ToolRegistry;
        $product = Product::with('variants.inventory')->first();

        if ($product && $product->variants->first()) {
            $variantId = $product->variants->first()->id;

            $result = $registry->execute('check_inventory', [
                'product_ids' => [$variantId],
            ]);

            expect($result)->toBeArray()
                ->and($result)->toHaveKey('success')
                ->and($result)->toHaveKey('inventory');
        } else {
            expect(true)->toBeTrue(); // Skip if no products
        }
    });

    test('execute get_product_reviews returns real reviews', function () {
        $registry = new ToolRegistry;
        $product = Product::first();

        $result = $registry->execute('get_product_reviews', [
            'product_id' => $product->id,
            'limit' => 5,
        ]);

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('success')
            ->and($result)->toHaveKey('reviews')
            ->and($result)->toHaveKey('average_rating');
    });

    test('execute returns error for unknown tool', function () {
        $registry = new ToolRegistry;

        $result = $registry->execute('unknown_tool', []);

        expect($result)->toBeArray()
            ->and($result['success'])->toBeFalse()
            ->and($result)->toHaveKey('error');
    });

    test('search_products filters by category', function () {
        $registry = new ToolRegistry;

        $result = $registry->execute('search_products', [
            'category' => 'floral',
            'max_results' => 5,
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['products'])->toBeArray();
    });

    test('search_products filters by price range', function () {
        $registry = new ToolRegistry;

        $result = $registry->execute('search_products', [
            'min_price' => 1000,
            'max_price' => 5000,
            'max_results' => 10,
        ]);

        expect($result['success'])->toBeTrue();

        foreach ($result['products'] as $product) {
            expect($product['price'])->toBeGreaterThanOrEqual(1000)
                ->and($product['price'])->toBeLessThanOrEqual(5000);
        }
    });
});
