<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

/** @extends Factory<\App\Models\ProductImage> */
class ProductImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'path'       => '/images/placeholder.jpg',
            'alt'        => $this->faker->words(3, true),
            'sort'       => 0,
            'is_hero'    => false,
            'rank'       => 0,
        ];
    }
}

