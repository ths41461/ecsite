<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_token', 64);
            $table->json('answers_json');
            $table->string('profile_type', 50);
            $table->json('profile_data_json');
            $table->json('recommended_product_ids');
            $table->timestamps();

            $table->index('session_token');
            $table->index('user_id');
            $table->index('profile_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_results');
    }
};
