<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'details_completed_at')) {
                $table->dateTime('details_completed_at')->nullable()->after('cancellation_emailed_at');
            }
            if (!Schema::hasColumn('orders', 'payment_started_at')) {
                $table->dateTime('payment_started_at')->nullable()->after('details_completed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_started_at')) {
                $table->dropColumn('payment_started_at');
            }
            if (Schema::hasColumn('orders', 'details_completed_at')) {
                $table->dropColumn('details_completed_at');
            }
        });
    }
};
