<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        if (Brand::count() > 0) {
            return;
        }

        $brands = [
            // 日本のブランド (Japanese Brands)
            ['name' => 'SHIRO', 'slug' => 'shiro', 'country' => '日本', 'tier' => 'プレミアム'],
            ['name' => 'COSME DECORTE', 'slug' => 'cosme-decorte', 'country' => '日本', 'tier' => 'プレミアム'],
            ['name' => 'Issey Miyake', 'slug' => 'issey-miyake', 'country' => '日本', 'tier' => 'ラグジュアリー'],
            ['name' => '資生堂', 'slug' => 'shiseido', 'country' => '日本', 'tier' => 'ラグジュアリー'],
            ['name' => 'ハナエモリ', 'slug' => 'hanae-mori', 'country' => '日本', 'tier' => 'デザイナー'],
            ['name' => 'ケンゾー', 'slug' => 'kenzo', 'country' => '日本/フランス', 'tier' => 'デザイナー'],
            ['name' => 'アナスイ', 'slug' => 'anna-sui', 'country' => '日本/アメリカ', 'tier' => 'デザイナー'],
            ['name' => 'ポール＆ジョー', 'slug' => 'paul-joe', 'country' => '日本/フランス', 'tier' => 'デザイナー'],
            ['name' => 'ジルスチュアート', 'slug' => 'jill-stuart', 'country' => '日本/アメリカ', 'tier' => 'デザイナー'],
            ['name' => 'エクセル', 'slug' => 'excel', 'country' => '日本', 'tier' => 'プチプラ'],

            // 海外のブランド (International Brands)
            ['name' => 'シャネル', 'slug' => 'chanel', 'country' => 'フランス', 'tier' => 'ウルトララグジュアリー'],
            ['name' => 'ディオール', 'slug' => 'dior', 'country' => 'フランス', 'tier' => 'ウルトララグジュアリー'],
            ['name' => 'トムフォード', 'slug' => 'tom-ford', 'country' => 'アメリカ', 'tier' => 'ウルトララグジュアリー'],
            ['name' => 'グッチ', 'slug' => 'gucci', 'country' => 'イタリア', 'tier' => 'ラグジュアリー'],
            ['name' => 'ヴェルサーチ', 'slug' => 'versace', 'country' => 'イタリア', 'tier' => 'ラグジュアリー'],
            ['name' => 'クロエ', 'slug' => 'chloe', 'country' => 'フランス', 'tier' => 'ラグジュアリー'],
            ['name' => 'イヴ・サンローラン', 'slug' => 'ysl', 'country' => 'フランス', 'tier' => 'ラグジュアリー'],
            ['name' => 'プラダ', 'slug' => 'prada', 'country' => 'イタリア', 'tier' => 'ラグジュアリー'],
            ['name' => 'ジョルジオ・アルマーニ', 'slug' => 'armani', 'country' => 'イタリア', 'tier' => 'ラグジュアリー'],
            ['name' => 'ジョーマローン', 'slug' => 'jo-malone', 'country' => 'イギリス', 'tier' => 'プレミアム'],
        ];

        foreach ($brands as $brand) {
            Brand::create([
                'name' => $brand['name'],
                'slug' => $brand['slug'].'-'.Str::lower(Str::random(4)),
            ]);
        }
    }
}
