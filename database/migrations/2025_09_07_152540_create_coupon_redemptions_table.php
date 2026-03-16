<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('redeemed_at')->useCurrent();

            // Enforce "once per order" regardless of user (guests included)
            $table->unique(['coupon_id', 'order_id'], 'coupon_once_per_order');

            // Helpful for analytics/limits; not a hard cap
            $table->index(['coupon_id', 'user_id'], 'coupon_user_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
    }
};
