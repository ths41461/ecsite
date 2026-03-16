<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('founded', 10)->nullable()->after('slug');
            $table->string('founder', 100)->nullable()->after('founded');
            $table->string('origin', 100)->nullable()->after('founder');
            $table->string('category', 50)->nullable()->after('origin');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['founded', 'founder', 'origin', 'category']);
        });
    }
};
