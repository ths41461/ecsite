<?php

use App\Models\ProductVariant;
use App\Models\Inventory;
use Illuminate\Database\QueryException;

it('enforces unique SKU at the database layer', function () {
    ProductVariant::factory()->create(['sku' => 'SKU-UNIQ00001']);

    expect(fn () => ProductVariant::factory()->create(['sku' => 'SKU-UNIQ00001']))
        ->toThrow(QueryException::class);
});

it('finds a variant by SKU', function () {
    $variant = ProductVariant::factory()->create(['sku' => 'SKU-FINDME1234']);
    $found   = ProductVariant::findBySku('SKU-FINDME1234');

    expect($found->id)->toBe($variant->id);
});

it('enforces inventory foreign key to variant', function () {
    // must fail if variant doesn't exist
    expect(function () {
        Inventory::create([
            'product_variant_id' => 999999, 'stock' => 10, 'reserved' => 0, 'threshold' => 1,
        ]);
    })->toThrow(QueryException::class);
});
