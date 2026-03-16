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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable(); // Using string to support UUID if needed
            $table->string('user_name')->nullable(); // Store name for reference even if user is deleted
            $table->string('action')->nullable(); // create, update, delete, restore, etc.
            $table->string('model_type')->nullable(); // Model class name (e.g., 'App\Models\Product')
            $table->string('model_id')->nullable(); // Using string to support UUID if needed
            $table->string('model_name')->nullable(); // Name of the model instance for reference
            $table->json('old_values')->nullable(); // Store old values as JSON
            $table->json('new_values')->nullable(); // Store new values as JSON
            $table->text('url')->nullable(); // URL where the action occurred
            $table->ipAddress('ip_address')->nullable(); // IP address of the user
            $table->text('user_agent')->nullable(); // User agent string
            $table->timestamps();

            // Indexes for performance
            $table->index(['model_type', 'model_id']);
            $table->index(['user_id']);
            $table->index(['action']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};