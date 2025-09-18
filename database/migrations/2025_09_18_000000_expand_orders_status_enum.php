<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Allow webhook handlers to persist the paid state without truncation warnings.
        DB::statement(<<<SQL
            ALTER TABLE `orders`
            MODIFY COLUMN `status`
            ENUM('ordered','processing','paid','shipped','delivered','canceled','refunded')
            NOT NULL DEFAULT 'ordered'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(<<<SQL
            ALTER TABLE `orders`
            MODIFY COLUMN `status`
            ENUM('ordered','processing','shipped','delivered','canceled','refunded')
            NOT NULL DEFAULT 'ordered'
        SQL);
    }
};

