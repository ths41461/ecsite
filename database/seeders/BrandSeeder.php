<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        if (Brand::count() > 0) return;

        Brand::factory()->count(20)->create();
    }
}
