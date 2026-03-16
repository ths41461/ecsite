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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'staff', 'viewer'])->default('viewer')->after('remember_token');
            } else {
                // First update any existing invalid values to 'viewer'
                \DB::statement("UPDATE users SET role = 'viewer' WHERE role NOT IN ('admin', 'staff', 'viewer')");
                // Then modify the column
                $table->enum('role', ['admin', 'staff', 'viewer'])->default('viewer')->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};