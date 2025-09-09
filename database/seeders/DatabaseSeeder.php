<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Lookups first (order/payment/shipment statuses, etc.)
        $this->call([
            LookupSeeder::class,
        ]);

        // Core catalog
        $this->call([
            BrandSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            ProductVariantSeeder::class,
            ProductImageSeeder::class,
            VariantInventorySeeder::class,
        ]);

        // If you have Stage-3 backfills (e.g., CategoryProductBackfillSeeder), call them here too.
        $this->call([CategoryProductBackfillSeeder::class, ProductImageHeroBackfillSeeder::class]);
    }
}
