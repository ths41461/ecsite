<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // A) lookup
        if (!Schema::hasTable('shipment_statuses')) {
            Schema::create('shipment_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();   // pending, packed, in_transit, delivered, returned
                $table->string('name', 100);
                $table->timestamps();
            });
        }

        // B) shipments.shipment_status_id
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'shipment_status_id')) {
                $table->unsignedBigInteger('shipment_status_id')->nullable()->after('id');
                $table->index('shipment_status_id', 'shipments_status_idx');
            }
        });

        // Drop existing FK by actual name if present, then add the desired FK
        $existingFk = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'shipments')
            ->where('COLUMN_NAME', 'shipment_status_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');
        if ($existingFk) {
            DB::statement('ALTER TABLE `shipments` DROP FOREIGN KEY `'.$existingFk.'`');
        }
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreign('shipment_status_id')
                ->references('id')->on('shipment_statuses')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });

        // C) tracking events
        if (!Schema::hasTable('shipment_tracks')) {
            Schema::create('shipment_tracks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shipment_id');
                $table->string('carrier', 100)->nullable();    // e.g., yamato, sagawa, jp_post
                $table->string('track_no', 191)->nullable();
                $table->string('status', 50);                  // echo from carrier (e.g., in_transit, delivered)
                $table->json('raw_event_json')->nullable();    // full webhook payload (if any)
                $table->timestamp('event_time')->useCurrent();
                $table->timestamps();

                $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete()->cascadeOnUpdate();
                $table->index(['shipment_id', 'event_time'], 'shipment_tracks_time_idx');
            });
        }
    }

    public function down(): void
    {
        // Dropping the table also drops its FKs and indexes
        Schema::dropIfExists('shipment_tracks');

        // Drop FK on shipments.shipment_status_id if it exists
        $existingFk = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'shipments')
            ->where('COLUMN_NAME', 'shipment_status_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');
        if ($existingFk) {
            DB::statement('ALTER TABLE `shipments` DROP FOREIGN KEY `'.$existingFk.'`');
        }

        // Drop index if present
        $idxExists = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'shipments')
            ->where('INDEX_NAME', 'shipments_status_idx')
            ->exists();
        if ($idxExists) {
            DB::statement('ALTER TABLE `shipments` DROP INDEX `shipments_status_idx`');
        }

        // keep column unless you truly want to drop it
        // Schema::table('shipments', fn (Blueprint $t) => $t->dropColumn('shipment_status_id'));

        Schema::dropIfExists('shipment_statuses');
    }
};

