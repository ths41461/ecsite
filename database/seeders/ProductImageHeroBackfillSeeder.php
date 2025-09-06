<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductImageHeroBackfillSeeder extends Seeder
{
    public function run(): void
    {
        // Normalize ranks first (avoid NULL)
        DB::table('product_images')->update(['rank' => DB::raw('COALESCE(`rank`, 0)')]);

        // Promote a hero for products that have images but no hero image yet.
        $productIdsNeedingHero = DB::table('products')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('product_images')
                  ->whereColumn('product_images.product_id', 'products.id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('product_images')
                  ->whereColumn('product_images.product_id', 'products.id')
                  ->where('product_images.is_hero', 1);
            })
            ->pluck('id');

        foreach ($productIdsNeedingHero as $pid) {
            $img = DB::table('product_images')
                ->where('product_id', $pid)
                ->orderBy('rank')
                ->orderBy('id')
                ->first();

            if ($img) {
                DB::table('product_images')
                    ->where('id', $img->id)
                    ->update(['is_hero' => 1, 'rank' => 0]);
            }
        }
    }
}
