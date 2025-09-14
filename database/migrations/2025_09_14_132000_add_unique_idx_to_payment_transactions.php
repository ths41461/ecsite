<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Pre-clean duplicates so the unique index can be created safely.
        // Keep the lowest id per (provider, ext_id) where ext_id IS NOT NULL; delete the rest.
        // MySQL treats NULLs as distinct in UNIQUE, so no need to touch NULL ext_id rows.
        DB::statement(<<<SQL
            DELETE t1 FROM payment_transactions t1
            INNER JOIN payment_transactions t2
              ON t1.provider = t2.provider
             AND t1.ext_id = t2.ext_id
             AND t1.id > t2.id
            WHERE t1.ext_id IS NOT NULL AND t2.ext_id IS NOT NULL
        SQL);

        Schema::table('payment_transactions', function (Blueprint $t) {
            // Unique on provider + ext_id to dedupe webhook retries
            $t->unique(['provider', 'ext_id'], 'tx_provider_extid');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $t) {
            $t->dropUnique('tx_provider_extid');
        });
    }
};
