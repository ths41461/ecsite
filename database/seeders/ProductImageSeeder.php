<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        // add 3 images per product; first becomes hero (Stage 3 enforces one hero)
        Product::query()->select('id')->chunkById(200, function ($products) {
            foreach ($products as $product) {
                if ($product->images()->count() > 0) continue;

                // attach public demo images under /perfume-images
                $paths = [
                    '/perfume-images/perfume-1.png',
                    '/perfume-images/perfume-2.png',
                    '/perfume-images/perfume-3.png',
                    '/perfume-images/perfume-4.png',
                    '/perfume-images/perfume-5.png',
                    '/perfume-images/perfume-6.png',
                    '/perfume-images/perfume-7.png',
                ];

                shuffle($paths);
                $pick = array_slice($paths, 0, 3);

                $images = collect($pick)->map(function ($p) use ($product) {
                    return ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $p,
                        'alt' => 'Perfume image',
                        'sort' => 0,
                        'is_hero' => false,
                        'rank' => 0,
                    ]);
                });

                $first = $images->first();
                $first->is_hero = true;
                $first->rank = 0;
                $first->save();

                // rank the rest 1..n
                $rank = 1;
                foreach ($images->slice(1) as $img) {
                    $img->rank = $rank++;
                    $img->save();
                }
            }
        });
    }
}
