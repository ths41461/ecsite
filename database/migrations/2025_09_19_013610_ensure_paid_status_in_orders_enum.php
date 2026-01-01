<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<SQL
            ALTER TABLE `orders`
            MODIFY COLUMN `status`
            ENUM('ordered','processing','paid','shipped','delivered','canceled','refunded')
            NOT NULL DEFAULT 'ordered'
        SQL);
    }

    public function down(): void
    {
        // Revert to enum values without 'paid' (assuming no 'paid' orders exist at rollback time)
        DB::statement(<<<SQL
            ALTER TABLE `orders`
            MODIFY COLUMN `status`
            ENUM('ordered','processing','shipped','delivered','canceled','refunded')
            NOT NULL DEFAULT 'ordered'
        SQL);
    }
};
