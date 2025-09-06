<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
{
    // --- 1) product_variants.sku UNIQUE (only if missing)
    $hasSkuUnique = collect(DB::select(
        "SELECT COUNT(1) AS c
         FROM information_schema.statistics
         WHERE table_schema = DATABASE()
           AND table_name = 'product_variants'
           AND index_name = 'product_variants_sku_unique'"
    ))->first()->c ?? 0;

    if ((int)$hasSkuUnique === 0) {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->unique('sku', 'product_variants_sku_unique');
        });
    }

    // --- 2) inventories.product_variant_id -> product_variants.id (RESTRICT)
    // Drop FK if it exists (name-agnostic) then re-add with RESTRICT rules.
    Schema::table('inventories', function (Blueprint $table) {
        try { $table->dropForeign(['product_variant_id']); } catch (\Throwable $e) {}
    });

    Schema::table('inventories', function (Blueprint $table) {
        $table->foreign('product_variant_id')
              ->references('id')->on('product_variants')
              ->restrictOnDelete()
              ->restrictOnUpdate();
    });

    // --- 3) Enforce 1:1 inventory per variant via unique(index) (only if missing)
    $hasInvUnique = collect(DB::select(
        "SELECT COUNT(1) AS c
         FROM information_schema.statistics
         WHERE table_schema = DATABASE()
           AND table_name = 'inventories'
           AND index_name = 'inventories_variant_unique'"
    ))->first()->c ?? 0;

    if ((int)$hasInvUnique === 0) {
        Schema::table('inventories', function (Blueprint $table) {
            $table->unique('product_variant_id', 'inventories_variant_unique');
        });
    }
}


    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            try { $table->dropUnique('inventories_variant_unique'); } catch (\Throwable $e) {}
            try { $table->dropForeign(['product_variant_id']); } catch (\Throwable $e) {}
        });

        Schema::table('product_variants', function (Blueprint $table) {
            try { $table->dropUnique('product_variants_sku_unique'); } catch (\Throwable $e) {}
        });
    }
};
