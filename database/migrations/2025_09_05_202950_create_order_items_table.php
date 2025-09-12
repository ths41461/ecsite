<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $t) {
                $t->id();

                // Parent order
                $t->foreignId('order_id')->constrained()->cascadeOnDelete();

                // Product + Variant (denormalized snapshot-friendly fields exist too)
                $t->foreignId('product_id')->constrained()->restrictOnDelete();
                $t->unsignedBigInteger('product_variant_id'); // FK optional; see note below

                // Snapshots (what the customer saw/paid at the time)
                $t->string('name_snapshot', 200);
                $t->string('sku_snapshot', 120);

                // Money in JPY (integer yen)
                $t->unsignedInteger('unit_price_yen');
                $t->unsignedSmallInteger('qty');
                $t->unsignedInteger('line_total_yen');

                $t->timestamps();

                $t->index(['order_id']);
                $t->index(['product_id', 'product_variant_id']);
            });

            // OPTIONAL strict FK:
            // Enable this ONLY if product_variants are not soft-deleted or recreated.
            // Schema::table('order_items', function (Blueprint $t) {
            //     $t->foreign('product_variant_id')
            //       ->references('id')->on('product_variants')
            //       ->restrictOnDelete()->restrictOnUpdate();
            // });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
