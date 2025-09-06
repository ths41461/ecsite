<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->words(3, true));
        return [
            'name'           => $name,
            'slug'           => Str::slug($name) . '-' . Str::lower(Str::random(6)),
            'brand_id'       => Brand::factory(),
            'category_id'    => Category::factory(),
            'short_desc'     => $this->faker->sentence(),
            'long_desc'      => $this->faker->paragraph(),
            'is_active'      => true,
            'featured'       => $this->faker->boolean(10),
            'attributes_json'=> ['notes' => ['top'=>'citrus','middle'=>'floral','base'=>'musk']],
            'meta_json'      => ['seo_title' => $name],
            'published_at'   => now(),
        ];
    }
}
