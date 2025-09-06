<?php

use Illuminate\Database\QueryException;
use App\Models\Product;
use App\Models\ProductImage;

it('enforces only one hero image per product at the DB level', function () {
    $product = Product::factory()->create();

    // first hero is fine
    ProductImage::factory()->create([
        'product_id' => $product->id,
        'is_hero'    => true,
        'rank'       => 0,
    ]);

    // second hero for same product should violate unique(hero_only)
    expect(function () use ($product) {
        ProductImage::factory()->create([
            'product_id' => $product->id,
            'is_hero'    => true,
            'rank'       => 1,
        ]);
    })->toThrow(QueryException::class);
});

it('returns the heroImage via the model relation', function () {
    $product = Product::factory()->create();

    // non-hero gallery
    ProductImage::factory()->count(2)->create([
        'product_id' => $product->id,
        'is_hero'    => false,
    ]);

    $hero = ProductImage::factory()->create([
        'product_id' => $product->id,
        'is_hero'    => true,
        'rank'       => 0,
    ]);

    $found = $product->heroImage()->first();
    expect($found?->id)->toBe($hero->id);
});

it('orders gallery images by rank', function () {
    $product = Product::factory()->create();

    $img2 = ProductImage::factory()->create(['product_id' => $product->id, 'rank' => 2, 'is_hero' => false]);
    $img0 = ProductImage::factory()->create(['product_id' => $product->id, 'rank' => 0, 'is_hero' => true]);
    $img1 = ProductImage::factory()->create(['product_id' => $product->id, 'rank' => 1, 'is_hero' => false]);

    $ordered = ProductImage::query()
        ->where('product_id', $product->id)
        ->orderBy('rank')
        ->pluck('id')
        ->all();

    expect($ordered)->toBe([$img0->id, $img1->id, $img2->id]);
});
