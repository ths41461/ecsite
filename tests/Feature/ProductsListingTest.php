<?php

use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\get;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;

beforeEach(function () {
    Artisan::call('migrate:fresh', ['--seed' => true]);
});

it('paginates products and preserves page param', function () {
    $res = get(route('products.index', ['page' => 2]))
        ->assertOk();
    $res->assertSee('Products');
    $res->assertSee('?page=1', false);
    $res->assertSee('?page=3', false);
});

it('searches by q with DB fallback', function () {
    $p = Product::first();
    get(route('products.index', ['q' => substr($p->name, 0, 5)]))
        ->assertOk()
        ->assertSee('Products')
        ->assertSee($p->name, false);
});

it('filters by brand/category and returns facets', function () {
    $brand = Brand::inRandomOrder()->first();
    $cat   = Category::inRandomOrder()->first();
    $res = get(route('products.index', ['brand' => $brand->slug]))->assertOk();
    $res->assertSee('Products');
    $res->assertSee($brand->name, false);

    $res2 = get(route('products.index', ['category' => $cat->slug]))->assertOk();
    $res2->assertSee($cat->name, false);
});

it('sorts by price asc and desc', function () {
    get(route('products.index', ['sort' => 'price_asc']))->assertOk();
    get(route('products.index', ['sort' => 'price_desc']))->assertOk();
});
