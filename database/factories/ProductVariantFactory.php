<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Models\Product;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id'      => Product::factory(),
            'sku'             => 'SKU-'.strtoupper(Str::random(8)),
            'option_json'     => ['volume' => $this->faker->randomElement(['30ml','50ml','100ml'])],
            'price_yen'       => $this->faker->numberBetween(3000, 20000),
            'sale_price_yen'  => $this->faker->boolean(30) ? $this->faker->numberBetween(2500, 18000) : null,
            'is_active'       => true,
        ];
    }
}