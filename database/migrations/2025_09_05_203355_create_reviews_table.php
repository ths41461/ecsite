<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_reviews_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('reviews', function (Blueprint $t) {
      $t->id();
      $t->foreignId('product_id')->constrained()->cascadeOnDelete();
      $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $t->tinyInteger('rating'); // 1..5 (validate in code)
      $t->text('body');
      $t->boolean('approved')->default(false);
      $t->timestamps();
      $t->index(['product_id','approved']);
    });
  }
  public function down(): void { Schema::dropIfExists('reviews'); }
};

