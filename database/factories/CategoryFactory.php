<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        // Japanese category names
        $categories = [
            '香水', 'コロン', 'ボディスプレーア', 'ローション', 
            'オードパルファム', 'オードトワレ', 'ミスト', 'クリーム', 
            'ジェル', 'オイル', 'デオドラント', 'フェイスミスト',
            'ヘアミスト', 'バスオイル', 'シャワージェル', 'ソープ',
            'キャンドル', 'ディフューザー', 'インセンス', 'アロマ',
            'スキンケア', 'メイク', 'ネイル', 'ヘアケア'
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