<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        if (Product::count() > 0) return;

        $brands = Brand::pluck('id')->all();
        $cats   = Category::pluck('id')->all();

        // ~120 products total (or adjust)
        Product::factory()
            ->count(120)
            ->state(function () use ($brands, $cats) {
                return [
                    'brand_id'    => fake()->randomElement($brands),
                    'category_id' => fake()->randomElement($cats),
                    'is_active'   => true,
                ];
            })
            ->create();
    }
}
