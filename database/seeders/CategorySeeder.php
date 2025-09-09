<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        if (Category::count() > 0) return;

        // 3 top-level categories with children (total ~12)
        $parents = Category::factory()->count(3)->create(['parent_id' => null, 'depth' => 0]);

        foreach ($parents as $p) {
            Category::factory()->count(3)->create([
                'parent_id' => $p->id,
                'depth'     => 1,
            ]);
        }
    }
}
