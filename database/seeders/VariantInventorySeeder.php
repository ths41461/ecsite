<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductVariant;
use App\Models\Inventory;

class VariantInventorySeeder extends Seeder
{
    public function run(): void
    {
        // Ensure every existing variant has an inventory row
        ProductVariant::query()->select('id')->chunkById(500, function ($variants) {
            foreach ($variants as $variant) {
                if (Inventory::query()->where('product_variant_id', $variant->id)->exists()) {
                    continue;
                }

                Inventory::create([
                    'product_variant_id' => $variant->id,
                    'stock'              => fake()->numberBetween(10, 50),
                    'safety_stock'       => fake()->numberBetween(0, 5),
                    'managed'            => true,
                ]);
            }
        });
    }
}
