<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Brand;
use App\Models\Category;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        // No Faker: deterministic, collision-safe values
        $suffix = strtoupper(Str::random(3)) . '-' . random_int(10, 99);
        $name   = 'Aroma ' . $suffix;
        $slug   = Str::slug($name) . '-' . strtolower(Str::random(6));

        return [
            'name'           => $name,
            'slug'           => $slug,
            'brand_id'       => Brand::factory(),
            'category_id'    => Category::factory(),
            'short_desc'     => 'Clean, modern scent.',
            'long_desc'      => 'Balanced composition with fresh top and warm base notes.',
            'is_active'      => true,
            'featured'       => (bool) random_int(0, 9) === 0, // ~10%
            'attributes_json'=> ['notes' => ['top'=>'citrus','middle'=>'floral','base'=>'musk']],
            'meta_json'      => ['seo_title' => $name],
            'published_at'   => now(),
        ];
    }
}
