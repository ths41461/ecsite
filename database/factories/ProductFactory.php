<?php

namespace Database\Factories\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Models\Brand;
use App\Models\Models\Category;
use Illuminate\Support\Str;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        return [
            'name'         => ucfirst($name),
            'slug'         => Str::slug($name.'-'.Str::random(6)),
            'brand_id'     => Brand::factory(),
            'category_id'  => Category::factory(),
            'short_desc'   => $this->faker->sentence(),
            'long_desc'    => $this->faker->paragraph(),
            'is_active'    => true,
            'featured'     => $this->faker->boolean(10),
            'attributes_json' => ['notes' => ['top'=>'citrus','middle'=>'floral','base'=>'musk']],
            'meta_json'    => ['seo_title' => $name],
            'published_at' => now(),
        ];
    }
}
