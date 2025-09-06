<?php

namespace Database\Factories\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Models\ProductVariant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'stock'              => $this->faker->numberBetween(3, 20),
            'safety_stock'       => $this->faker->numberBetween(0, 3),
            'managed'            => true,
            'updated_at'         => now(),
        ];
    }
}
