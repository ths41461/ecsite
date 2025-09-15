<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            if (!Schema::hasColumn('orders', 'coupon_code')) {
                $t->string('coupon_code', 40)->nullable()->after('discount_yen');
            }
            if (!Schema::hasColumn('orders', 'coupon_discount_yen')) {
                $t->integer('coupon_discount_yen')->default(0)->after('coupon_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            if (Schema::hasColumn('orders', 'coupon_discount_yen')) $t->dropColumn('coupon_discount_yen');
            if (Schema::hasColumn('orders', 'coupon_code')) $t->dropColumn('coupon_code');
        });
    }
};

