<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure the role column exists with the correct enum values
        if (Schema::hasColumn('users', 'role')) {
            // Update any existing invalid values to 'viewer'
            DB::statement("
                UPDATE users
                SET role = 'viewer'
                WHERE role NOT IN ('admin', 'staff', 'viewer', 'user')
            ");

            // Modify the column to be enum with correct values
            DB::statement("
                ALTER TABLE users
                MODIFY COLUMN role ENUM('admin', 'staff', 'viewer', 'user')
                NOT NULL DEFAULT 'viewer'
            ");
        } else {
            // Create the column if it doesn't exist
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'staff', 'viewer', 'user'])->default('viewer')->after('remember_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change back to a simple string column
        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role VARCHAR(255)
            NOT NULL DEFAULT 'user'
        ");
    }
};
