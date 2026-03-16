<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Product;

/** @extends Factory<\App\Models\ProductVariant> */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        // Japanese size options in ml
        $sizes = [10, 30, 50, 75, 100, 125, 150, 200];
        
        // Gender options
        $genders = ['men', 'women', 'unisex'];
        
        return [
            'product_id'     => Product::factory(),
            // unique, uppercase, readable, DB-constraint friendly
            'sku'            => 'SKU-' . \Illuminate\Support\Str::upper(fake()->unique()->bothify('##########')),
            'price_yen'      => fake()->numberBetween(500, 50000),
            'sale_price_yen' => null,
            'option_json'    => [
                'size_ml' => fake()->randomElement($sizes),
                'gender' => fake()->randomElement($genders)
            ],
            'is_active'      => true,
        ];
    }
}