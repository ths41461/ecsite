<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            if (!Schema::hasColumn('orders', 'confirmation_emailed_at')) {
                $t->dateTime('confirmation_emailed_at')->nullable()->after('inventory_decremented_at');
            }
            if (!Schema::hasColumn('orders', 'cancellation_emailed_at')) {
                $t->dateTime('cancellation_emailed_at')->nullable()->after('confirmation_emailed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            if (Schema::hasColumn('orders', 'cancellation_emailed_at')) {
                $t->dropColumn('cancellation_emailed_at');
            }
            if (Schema::hasColumn('orders', 'confirmation_emailed_at')) {
                $t->dropColumn('confirmation_emailed_at');
            }
        });
    }
};

