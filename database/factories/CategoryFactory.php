<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        // Japanese perfume category names
        $categories = [
            '香水', 'コロン', 'オードパルファム', 'オードトワレ', 
            'ミスト', 'デオドラント', 'フェイスミスト', 'ヘアミスト',
            'ボディスプレー', 'パフュームオイル', '香りのオイル',
            'ユニセックス香水', 'メンズ香水', 'レディース香水',
            '朝用香水', '夜用香水', 'デート用香水', 'ビジネス用香水'
        ];
        
        $name = $this->faker->randomElement($categories);
        return [
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(4)),
            'parent_id' => null,
        ];
    }

    public function childOf(?\App\Models\Category $parent = null)
    {
        return $this->state(function (array $attributes) use ($parent) {
            $parent = $parent ?: \App\Models\Category::factory()->create();
            return [
                'parent_id' => $parent->id,
                'depth'     => min(($parent->depth ?? 0) + 1, 2),
            ];
        });
    }
}