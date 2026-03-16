<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductImage;

class ProductImagePublicBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $publicImages = [
            '/perfume-images/perfume-1.png',
            '/perfume-images/perfume-2.png',
            '/perfume-images/perfume-3.png',
            '/perfume-images/perfume-4.png',
            '/perfume-images/perfume-5.png',
            '/perfume-images/perfume-6.png',
            '/perfume-images/perfume-7.png',
        ];

        // 1) Replace placeholder paths on existing ProductImage rows
        ProductImage::query()
            ->where(function ($q) {
                $q->whereNull('path')
                    ->orWhere('path', 'like', '%placeholder%')
                    ->orWhere('path', '=', 'images/placeholder.jpg');
            })
            ->chunkById(500, function ($rows) use ($publicImages) {
                foreach ($rows as $img) {
                    $img->path = $publicImages[array_rand($publicImages)];
                    $img->save();
                }
            });

        // 2) For products without images at all, create 3 and mark first hero
        Product::query()->select('id')->doesntHave('images')->chunkById(200, function ($products) use ($publicImages) {
            foreach ($products as $product) {
                $pick = array_slice($publicImages, 0, 3);
                shuffle($pick);

                $created = collect($pick)->map(function ($p) use ($product) {
                    return ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $p,
                        'alt' => 'Perfume image',
                        'sort' => 0,
                        'is_hero' => false,
                        'rank' => 0,
                    ]);
                });

                $first = $created->first();
                $first->is_hero = true;
                $first->rank = 0;
                $first->save();

                $rank = 1;
                foreach ($created->slice(1) as $img) {
                    $img->rank = $rank++;
                    $img->save();
                }
            }
        });

        // 3) Ensure each product has exactly one hero (demote extras)
        DB::statement('
            UPDATE product_images pi
            JOIN (
                SELECT product_id, MIN(id) AS keep_id
                FROM product_images
                WHERE is_hero = 1
                GROUP BY product_id
            ) s ON s.product_id = pi.product_id
            SET pi.is_hero = CASE WHEN pi.id = s.keep_id THEN 1 ELSE 0 END
            WHERE pi.is_hero = 1
        ');
    }
}
