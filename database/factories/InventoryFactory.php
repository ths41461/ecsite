<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ProductVariant;

/** @extends Factory<\App\Models\Inventory> */
class InventoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_variant_id' => \App\Models\ProductVariant::factory(),
            'stock'              => fake()->numberBetween(5, 50),
            'reserved'           => 0,
            'threshold'          => 2,
        ];
    }
}
