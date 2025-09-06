<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add columns if missing: is_hero (bool), rank (int)
        Schema::table('product_images', function (Blueprint $table) {
            if (!Schema::hasColumn('product_images', 'is_hero')) {
                $table->boolean('is_hero')->default(false)->after('product_id');
            }
            if (!Schema::hasColumn('product_images', 'rank')) {
                $table->unsignedInteger('rank')->default(0)->after('is_hero');
            }
        });

        // Add composite index only if it does not already exist. Do this outside the closure so
        // the existence check runs before the schema action is dispatched to the database.
        $rankIdxExists = (int) (collect(DB::select(
            "SELECT COUNT(1) AS c FROM information_schema.statistics\n"
          . "WHERE table_schema = DATABASE() AND table_name = 'product_images'\n"
          . "AND index_name = 'product_images_product_rank_idx'"
        ))->first()->c ?? 0);
        if ($rankIdxExists === 0) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->index(['product_id', 'rank'], 'product_images_product_rank_idx');
            });
        }

        // 2) Normalize existing data (quote reserved column names)
        DB::statement("UPDATE `product_images` SET `rank` = COALESCE(`rank`, 0)");

        // 3) If multiple heroes exist for the same product, demote all but the lowest-id to non-hero
        //    (No CTE/window functions; works under strict SQL modes)
        DB::statement("
            UPDATE `product_images` AS pi
            LEFT JOIN (
                SELECT product_id, MIN(id) AS keep_id
                FROM `product_images`
                WHERE is_hero = 1
                GROUP BY product_id
            ) AS s ON s.product_id = pi.product_id
            SET pi.is_hero = CASE WHEN pi.id = s.keep_id THEN 1 ELSE 0 END
            WHERE pi.is_hero = 1
              AND s.keep_id IS NOT NULL
        ");

        // 4) Functional unique index: only one hero per product (no generated column needed)
        $heroIdxExists = (int) (collect(DB::select(
            "SELECT COUNT(1) AS c FROM information_schema.statistics\n"
          . "WHERE table_schema = DATABASE() AND table_name = 'product_images'\n"
          . "AND index_name = 'product_images_one_hero_per_product'"
        ))->first()->c ?? 0);
        if ($heroIdxExists === 0) {
            // MySQL 8 functional index, allows multiple NULLs (non-hero rows),
            // but enforces uniqueness when is_hero = 1 by indexing product_id in that case.
            DB::statement(
                'CREATE UNIQUE INDEX `product_images_one_hero_per_product` '
                . 'ON `product_images` ((CASE WHEN `is_hero` THEN `product_id` ELSE NULL END))'
            );
        }
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            try {
                $table->dropUnique('product_images_one_hero_per_product');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('product_images_product_rank_idx');
            } catch (\Throwable $e) {
            }
        });

        // No generated column to drop when using functional index.

        // (We intentionally keep is_hero & rank. Drop if you need a full revert.)
    }
};
