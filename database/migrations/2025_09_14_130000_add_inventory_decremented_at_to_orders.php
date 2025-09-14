<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            if (!Schema::hasColumn('orders', 'inventory_decremented_at')) {
                $t->dateTime('inventory_decremented_at')->nullable()->after('canceled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            if (Schema::hasColumn('orders', 'inventory_decremented_at')) {
                $t->dropColumn('inventory_decremented_at');
            }
        });
    }
};

