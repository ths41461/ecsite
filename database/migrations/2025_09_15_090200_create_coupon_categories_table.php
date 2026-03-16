<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupon_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coupon_id')->constrained('coupons')->cascadeOnUpdate()->cascadeOnDelete();
            $t->foreignId('category_id')->constrained('categories')->cascadeOnUpdate()->cascadeOnDelete();
            $t->unique(['coupon_id', 'category_id'], 'coupon_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_categories');
    }
};

