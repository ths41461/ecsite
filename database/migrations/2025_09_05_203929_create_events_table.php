<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_events_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('events', function (Blueprint $t) {
      $t->id();
      $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
      $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $t->string('user_hash', 64)->nullable();
      $t->enum('event_type', ['view_pdp','add_to_cart','purchase','search_impr','search_click','wishlist_add','review_add']);
      $t->integer('value')->nullable();
      $t->dateTime('occurred_at');
      $t->json('meta_json')->nullable();
      $t->timestamps();
      $t->index(['product_id','event_type','occurred_at']);
    });
  }
  public function down(): void { Schema::dropIfExists('events'); }
};

