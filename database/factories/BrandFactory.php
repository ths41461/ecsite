<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Brand> */
class BrandFactory extends Factory
{
    public function definition(): array
    {
        // Japanese brand names
        $brands = [
            '花の香り', '森のアロマ', '風の香り', '月の香り',
            '星の香り', '海の香り', '山の香り', '空の香り',
            '朝の香り', '夜の香り', '春の香り', '夏の香り',
            '秋の香り', '冬の香り', '夢の香り', '恋の香り',
            '癒しの香り', 'リラックス', 'アロマテラピー', 'ナチュラル'
        ];
        
        $name = $this->faker->randomElement($brands);
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(5)),
        ];
    }
}