<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductVariant;

class ProductVariantSeeder extends Seeder
{
    public function run(): void
    {
        // Add 1-3 variants per product
        Product::query()->select('id')->chunkById(200, function ($products) {
            foreach ($products as $product) {
                if ($product->variants()->count() > 0) continue;

                $variantCount = fake()->numberBetween(1, 3);

                for ($i = 0; $i < $variantCount; $i++) {
                    $basePrice = fake()->numberBetween(5000, 50000); // 50-500 JPY
                    $hasSale = fake()->boolean(30); // 30% chance of sale

                    ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => 'SKU-' . strtoupper(fake()->bothify('##########')),
                        'price_yen' => $basePrice,
                        'sale_price_yen' => $hasSale ? fake()->numberBetween($basePrice * 0.7, $basePrice * 0.9) : null,
                        'option_json' => [
                            'gender' => fake()->randomElement(['men', 'women', 'unisex']),
                            'size_ml' => fake()->randomElement([50, 100, 150]),
                        ],
                        'is_active' => true,
                    ]);
                }
            }
        });
    }
}
