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
        // Japanese size options
        $sizes = ['50ml', '100ml', '150ml', '200ml'];
        
        return [
            'product_id'     => Product::factory(),
            // unique, uppercase, readable, DB-constraint friendly
            'sku'            => 'SKU-' . \Illuminate\Support\Str::upper(fake()->unique()->bothify('##########')),
            'price_yen'      => fake()->numberBetween(500, 50000),
            'sale_price_yen' => null,
            'option_json'    => fake()->randomElement([null, ['容量' => fake()->randomElement($sizes)]]),
            'is_active'      => true,
        ];
    }
}