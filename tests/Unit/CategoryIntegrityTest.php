<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

use App\Models\Product;
use App\Models\Category;

it('enforces FK: products.category_id must reference an existing category', function () {
    // create a product with a valid category
    $cat = Category::factory()->create();
    $prod = Product::factory()->create(['category_id' => $cat->id]);

    // Attempt to set an invalid category_id should fail at DB level
    expect(function () use ($prod) {
        $prod->update(['category_id' => 99999999]); // assume not exists
        // force flush to DB
        $prod->refresh();
    })->toThrow(QueryException::class);
});

it('prevents duplicate category_product pairs (unique pivot)', function () {
    $cat = Category::factory()->create();
    $prod = Product::factory()->create(['category_id' => $cat->id]);

    // Insert first pair
    DB::table('category_product')->insert([
        'category_id' => $cat->id,
        'product_id'  => $prod->id,
    ]);

    // Second insert of the same pair should fail on unique constraint
    expect(function () use ($cat, $prod) {
        DB::table('category_product')->insert([
            'category_id' => $cat->id,
            'product_id'  => $prod->id,
        ]);
    })->toThrow(QueryException::class);
});

it('lists products by category via the pivot', function () {
    $catA = Category::factory()->create();
    $catB = Category::factory()->create();

    $prodA1 = Product::factory()->create(['category_id' => $catA->id]);
    $prodA2 = Product::factory()->create(['category_id' => $catA->id]);
    $prodB1 = Product::factory()->create(['category_id' => $catB->id]);

    // attach pivot rows (in case ProductFactory didn't add them automatically)
    DB::table('category_product')->insertOrIgnore([
        ['category_id' => $catA->id, 'product_id' => $prodA1->id],
        ['category_id' => $catA->id, 'product_id' => $prodA2->id],
        ['category_id' => $catB->id, 'product_id' => $prodB1->id],
    ]);

    // List all product ids under catA via pivot
    $idsUnderA = DB::table('category_product')
        ->where('category_id', $catA->id)
        ->pluck('product_id')
        ->all();

    expect($idsUnderA)->toContain($prodA1->id, $prodA2->id)
        ->not->toContain($prodB1->id);
});
