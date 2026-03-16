<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            if (!Schema::hasColumn('orders', 'cart_session_id')) {
                $t->string('cart_session_id', 191)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('orders', 'cart_digest')) {
                $t->string('cart_digest', 64)->nullable()->after('cart_session_id');
            }
            if (!Schema::hasColumn('orders', 'pending_expires_at')) {
                $t->dateTime('pending_expires_at')->nullable()->after('ordered_at');
            }
            $t->index(['cart_session_id', 'ordered_at'], 'orders_cart_session_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            if (Schema::hasColumn('orders', 'pending_expires_at')) {
                $t->dropColumn('pending_expires_at');
            }
            if (Schema::hasColumn('orders', 'cart_digest')) {
                $t->dropColumn('cart_digest');
            }
            if (Schema::hasColumn('orders', 'cart_session_id')) {
                $t->dropColumn('cart_session_id');
            }
        });
    }
};

