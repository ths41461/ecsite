<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupon_products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coupon_id')->constrained('coupons')->cascadeOnUpdate()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $t->unique(['coupon_id', 'product_id'], 'coupon_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_products');
    }
};

