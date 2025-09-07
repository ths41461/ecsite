<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // A) lookup table for payment statuses
        if (!Schema::hasTable('payment_statuses')) {
            Schema::create('payment_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();   // pending, authorized, captured, failed, refunded
                $table->string('name', 100);
                $table->timestamps();
            });
        }

        // B) payments.payment_status_id (nullable first; backfill via seeder)
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payment_status_id')) {
                $table->unsignedBigInteger('payment_status_id')->nullable()->after('id');
                $table->index('payment_status_id', 'payments_status_idx');
            }
        });

        // add / re-add FK with RESTRICT rules (name-agnostic)
        // Find existing FK by name using information_schema to avoid exceptions
        $existingFk = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'payments')
            ->where('COLUMN_NAME', 'payment_status_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');
        if ($existingFk) {
            DB::statement('ALTER TABLE `payments` DROP FOREIGN KEY `'.$existingFk.'`');
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('payment_status_id')
                ->references('id')->on('payment_statuses')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });

        // C) payment transactions (auditable log from PSP, mock or real)
        if (!Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_id');
                $table->string('provider', 100);                 // e.g., mock, stripe, paypal
                $table->string('ext_id', 191)->nullable();        // gateway-side id
                $table->unsignedBigInteger('amount_yen')->nullable();
                $table->string('currency', 10)->default('JPY');
                $table->string('status', 50);                    // echo of PSP result (authorized, captured, failed...)
                $table->json('payload_json')->nullable();        // raw PSP payload for debugging
                $table->timestamp('occurred_at')->useCurrent();
                $table->timestamps();

                $table->foreign('payment_id')->references('id')->on('payments')->cascadeOnDelete()->cascadeOnUpdate();
                $table->index(['payment_id', 'occurred_at'], 'payment_tx_payment_time_idx');
            });
        }
    }

    public function down(): void
    {
        // Dropping the table also drops its FKs and indexes
        Schema::dropIfExists('payment_transactions');

        // Drop FK on payments.payment_status_id if it exists
        $existingFk = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'payments')
            ->where('COLUMN_NAME', 'payment_status_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');
        if ($existingFk) {
            DB::statement('ALTER TABLE `payments` DROP FOREIGN KEY `'.$existingFk.'`');
        }

        // Drop index if present
        $idxExists = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'payments')
            ->where('INDEX_NAME', 'payments_status_idx')
            ->exists();
        if ($idxExists) {
            DB::statement('ALTER TABLE `payments` DROP INDEX `payments_status_idx`');
        }

        // keep the column for safety; drop if you truly need a full revert:
        // Schema::table('payments', fn (Blueprint $t) => $t->dropColumn('payment_status_id'));

        Schema::dropIfExists('payment_statuses');
    }
};
