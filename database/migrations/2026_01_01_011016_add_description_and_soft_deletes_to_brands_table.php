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
        // Add description column if it doesn't exist
        if (!Schema::hasColumn('brands', 'description')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->text('description')->nullable()->after('slug');
            });
        }

        // Add deleted_at column for soft deletes if it doesn't exist
        if (!Schema::hasColumn('brands', 'deleted_at')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->timestamp('deleted_at')->nullable()->after('updated_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['description', 'deleted_at']);
        });
    }
};
