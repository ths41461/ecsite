<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        if (Category::count() > 0) {
            return;
        }

        // 親カテゴリー (Parent Categories)
        $parentCategories = [
            ['name' => 'オードパルファム', 'slug' => 'eau-de-parfum'],
            ['name' => 'オードトワレ', 'slug' => 'eau-de-toilette'],
            ['name' => 'フレグランスミスト', 'slug' => 'fragrance-mist'],
            ['name' => 'パルファム', 'slug' => 'parfum'],
        ];

        $parents = [];
        foreach ($parentCategories as $cat) {
            $parents[] = Category::create([
                'name' => $cat['name'],
                'slug' => $cat['slug'].'-'.Str::lower(Str::random(4)),
                'parent_id' => null,
                'depth' => 0,
            ]);
        }

        // 子カテゴリー (Child Categories)
        $childCategories = [
            // EDP Children (parent 0)
            ['name' => 'フローラル EDP', 'parent' => 0],
            ['name' => 'ウッディ EDP', 'parent' => 0],
            ['name' => 'オリエンタル EDP', 'parent' => 0],
            ['name' => 'フルーティー EDP', 'parent' => 0],
            ['name' => 'レザー EDP', 'parent' => 0],
            ['name' => 'シトラス EDP', 'parent' => 0],
            // EDT Children (parent 1)
            ['name' => 'フレッシュ EDT', 'parent' => 1],
            ['name' => 'シトラス EDT', 'parent' => 1],
            ['name' => 'アロマティック EDT', 'parent' => 1],
            ['name' => 'フルーティー EDT', 'parent' => 1],
            ['name' => 'フローラル EDT', 'parent' => 1],
            ['name' => 'ウッディ EDT', 'parent' => 1],
            // Mist Children (parent 2)
            ['name' => 'ボディミスト', 'parent' => 2],
            ['name' => 'ヘアミスト', 'parent' => 2],
            ['name' => 'パルファムオイル', 'parent' => 2],
            // Parfum Children (parent 3)
            ['name' => 'オリエンタル Parfum', 'parent' => 3],
        ];

        foreach ($childCategories as $cat) {
            Category::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']).'-'.Str::lower(Str::random(4)),
                'parent_id' => $parents[$cat['parent']]->id,
                'depth' => 1,
            ]);
        }
    }
}
