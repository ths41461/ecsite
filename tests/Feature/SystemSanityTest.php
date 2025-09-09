<?php

use App\Models\{Brand, Category, Product, ProductVariant, Inventory};
use Illuminate\Support\Facades\Artisan;

it('wires catalog, inventory, scout, and queue', function () {
    // 1) Minimal catalog via factories
    $brand = Brand::factory()->create();
    $cat   = Category::factory()->create();
    $product = Product::factory()->create([
        'brand_id' => $brand->id,
        'category_id' => $cat->id,
        'is_active' => true,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_active' => true,
    ]);
    Inventory::factory()->create([
        'product_variant_id' => $variant->id,
        'stock' => 5,
        'safety_stock' => 1,
        'managed' => true,
    ]);

    // 2) Relations hang together
    $product->load('brand', 'category', 'variants.inventory', 'images');
    expect($product->variants)->toHaveCount(1);
    expect($product->variants->first()->inventory->stock)->toBeGreaterThan(0);

    // 3) Scout import + lightweight search (ok if empty results, shouldn’t error)
    Artisan::call('scout:sync-index-settings');
    Artisan::call('scout:import', ['model' => Product::class]);
    // search call should not throw
    $results = Product::search($product->name)->take(3)->get();
    expect($results)->toBeInstanceOf(\Illuminate\Support\Collection::class);

    // 4) Queue boots (single cycle)
    Artisan::call('queue:work', ['--once' => true]);
    expect(true)->toBeTrue();
});
