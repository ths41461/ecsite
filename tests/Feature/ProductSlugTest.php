<?php

// tests/Feature/ProductSlugTest.php

use Illuminate\Support\Facades\Route;
use App\Models\Product;
use Illuminate\Database\QueryException;

it('resolves a Product by slug via route model binding', function () {
    // ✅ ensure route model binding runs
    Route::middleware('web')->get('/_t/products/{product}', fn (Product $product) => response()->json([
        'id' => $product->id,
        'slug' => $product->slug,
    ]));

    $product = Product::factory()->create();

    $this->get("/_t/products/{$product->slug}")
        ->assertOk()
        ->assertJson([
            'id' => $product->id,
            'slug' => $product->slug,
        ]);
});

it('enforces unique slugs at the database layer', function () {
    $p1 = Product::factory()->create(['slug' => 'unique-slug-123']);
    expect(fn () => Product::factory()->create(['slug' => 'unique-slug-123']))
        ->toThrow(QueryException::class);
});
