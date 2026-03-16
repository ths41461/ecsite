<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $t) {
            if (!Schema::hasColumn('coupons', 'exclude_sale_items')) {
                $t->boolean('exclude_sale_items')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('coupons', 'min_subtotal_yen')) {
                $t->integer('min_subtotal_yen')->nullable()->after('exclude_sale_items');
            }
            if (!Schema::hasColumn('coupons', 'max_discount_yen')) {
                $t->integer('max_discount_yen')->nullable()->after('min_subtotal_yen');
            }
            if (!Schema::hasColumn('coupons', 'max_uses_per_user')) {
                $t->integer('max_uses_per_user')->nullable()->after('max_discount_yen');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $t) {
            if (Schema::hasColumn('coupons', 'max_uses_per_user')) $t->dropColumn('max_uses_per_user');
            if (Schema::hasColumn('coupons', 'max_discount_yen')) $t->dropColumn('max_discount_yen');
            if (Schema::hasColumn('coupons', 'min_subtotal_yen')) $t->dropColumn('min_subtotal_yen');
            if (Schema::hasColumn('coupons', 'exclude_sale_items')) $t->dropColumn('exclude_sale_items');
        });
    }
};

