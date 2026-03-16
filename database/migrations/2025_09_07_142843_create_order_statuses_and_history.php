<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // A) lookup table for order statuses
        if (!Schema::hasTable('order_statuses')) {
            Schema::create('order_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();   // e.g., pending, paid, fulfilled, cancelled, refunded
                $table->string('name', 100);            // human label (JP/EN as you prefer)
                $table->timestamps();
            });
        }

        // B) orders.order_status_id (nullable first; we’ll backfill via seeder)
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_status_id')) {
                $table->unsignedBigInteger('order_status_id')->nullable()->after('id');
                $table->index('order_status_id', 'orders_status_idx');
            }
        });

        // Ensure no broken refs; drop FK if exists to recreate with rules
        // Use information_schema to avoid exceptions when FK is missing or differently named.
        $existingFk = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'orders')
            ->where('COLUMN_NAME', 'order_status_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($existingFk) {
            DB::statement('ALTER TABLE `orders` DROP FOREIGN KEY `'.$existingFk.'`');
        }

        Schema::table('orders', function (Blueprint $table) {
            // Add the desired FK only if it doesn't already exist
            $table->foreign('order_status_id')
                ->references('id')->on('order_statuses')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });

        // C) history table
        if (!Schema::hasTable('order_status_history')) {
            Schema::create('order_status_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('from_status_id')->nullable(); // null for first state
                $table->unsignedBigInteger('to_status_id');
                $table->unsignedBigInteger('changed_by')->nullable();     // optional: user/admin id
                $table->timestamp('changed_at')->useCurrent();
                $table->timestamps();

                $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreign('from_status_id')->references('id')->on('order_statuses')->restrictOnDelete()->restrictOnUpdate();
                $table->foreign('to_status_id')->references('id')->on('order_statuses')->restrictOnDelete()->restrictOnUpdate();

                $table->index(['order_id', 'changed_at'], 'order_history_order_time_idx');
            });
        }
    }

    public function down(): void
    {
        // Dropping the table also drops its FKs
        Schema::dropIfExists('order_status_history');

        // Drop FK on orders.order_status_id if it exists
        $existingFk = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'orders')
            ->where('COLUMN_NAME', 'order_status_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');
        if ($existingFk) {
            DB::statement('ALTER TABLE `orders` DROP FOREIGN KEY `'.$existingFk.'`');
        }

        // Drop index if present
        $idxExists = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'orders')
            ->where('INDEX_NAME', 'orders_status_idx')
            ->exists();
        if ($idxExists) {
            DB::statement('ALTER TABLE `orders` DROP INDEX `orders_status_idx`');
        }

        // keep the column (safe), or uncomment to drop:
        // Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('order_status_id'));

        Schema::dropIfExists('order_statuses');
    }
};
