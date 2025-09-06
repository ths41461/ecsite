<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryProductBackfillSeeder extends Seeder
{
    public function run(): void
    {
        // Insert (category_id, product_id) pairs where products.category_id is set
        DB::statement("
            INSERT IGNORE INTO category_product (category_id, product_id)
            SELECT DISTINCT category_id, id
            FROM products
            WHERE category_id IS NOT NULL
        ");
    }
}
