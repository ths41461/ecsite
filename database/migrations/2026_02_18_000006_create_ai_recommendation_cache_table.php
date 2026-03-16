<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_recommendation_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 255)->unique();
            $table->string('context_hash', 64);
            $table->json('product_ids_json');
            $table->text('explanation')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
            $table->index('context_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_recommendation_cache');
    }
};
