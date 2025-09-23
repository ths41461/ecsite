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
        // Japanese product names and descriptions
        $productNames = [
            'ローズアロマ', 'ラベンダーエッセンス', 'バニラフレグランス', 'サンダルウッドパフューム',
            'ベルガモットミスト', 'ゼラニウムオイル', 'ユーカリスプレー', 'シトラスコロン',
            'ジャスミンローション', 'フリージアジェル', 'ローズウッドクリーム', 'スイートオレンジアロマ',
            'ペパーミントミスト', 'ローズマリーオイル', 'ゼラニウムウォーター', 'サンダルウッドクリーム',
            'バニラエキス', 'ラベンダーミスト', 'ローズオードパルファム', 'ユーカリディフューザー'
        ];
        
        $descriptions = [
            '清らかで現代的な香り。',
            '新鮮なトップノートと暖かいベースノートのバランスの取れた構成。',
            '上品で洗練されたフレグランス。',
            '心地よい花の香りがリラックス効果をもたらします。',
            'ナチュラルな香りで日常に癒しを。',
            '上質な香料を使用した贅沢なアロマ。',
            '四季を通じて楽しめる心地よい香り。',
            '洗練された香りで特別な時間を演出します。'
        ];
        
        $name = $this->faker->randomElement($productNames) . ' ' . strtoupper(Str::random(3)) . '-' . random_int(10, 99);
        $slug = Str::slug($name) . '-' . strtolower(Str::random(6));

        return [
            'name'           => $name,
            'slug'           => $slug,
            'brand_id'       => Brand::factory(),
            'category_id'    => Category::factory(),
            'short_desc'     => $this->faker->randomElement($descriptions),
            'long_desc'      => $this->faker->randomElement($descriptions),
            'is_active'      => true,
            'featured'       => (bool) random_int(0, 9) === 0, // ~10%
            'attributes_json' => [
                'notes' => [
                    'top' => 'シトラス',
                    'middle' => 'フローラル',
                    'base' => 'マスク'
                ]
            ],
            'meta_json'      => ['seo_title' => $name],
            'published_at'   => now(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (\App\Models\Product $product) {
            // In production/seeding we mirror products.category_id into the pivot.
            // Skip during unit tests so tests can control duplicates explicitly.
            if ($product->category_id && !app()->runningUnitTests()) {
                $product->categories()->syncWithoutDetaching([$product->category_id]);
            }
        });
    }
}