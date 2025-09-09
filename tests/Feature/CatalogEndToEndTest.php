<?php

use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\get;
use App\Models\Product;

beforeEach(function () {
    // Rebuild + seed the testing DB for a clean, realistic state
    Artisan::call('migrate:fresh', ['--seed' => true]);
});

it('seeds products and renders them on the /products listing with pagination', function () {
    // 1) Seed sanity: we expect at least one product
    $count = Product::count();
    expect($count)->toBeGreaterThan(0, 'Expected seeded products, got 0');

    // 2) Choose a product that should appear on page 1
    // Your controller orders by latest created_at desc.
    $firstPageProduct = Product::query()->orderByDesc('created_at')->first();
    expect($firstPageProduct)->not->toBeNull();

    // 3) Hit the listing
    $response = get(route('products.index'))
        ->assertOk()
        // Page heading renders (basic hydration)
        ->assertSee('Products');

    // 4) Product name appears (confirms props mapping + grid render)
    $response->assertSee($firstPageProduct->name, false);

    // 5) Paginator renders (labels may be « Previous / Next » depending on locale)
    // We just assert the presence of one typical label.
    $response->assertSee('Next', false);
});
