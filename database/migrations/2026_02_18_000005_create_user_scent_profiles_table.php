<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_scent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('profile_type', 50);
            $table->json('profile_data_json');
            $table->json('preferences_json');
            $table->timestamps();

            $table->unique('user_id');
            $table->index('profile_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_scent_profiles');
    }
};
