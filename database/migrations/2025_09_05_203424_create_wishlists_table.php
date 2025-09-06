<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_wishlists_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('wishlists', function (Blueprint $t) {
      $t->id();
      $t->foreignId('user_id')->constrained()->cascadeOnDelete();
      $t->foreignId('product_id')->constrained()->cascadeOnDelete();
      $t->timestamp('created_at')->useCurrent();
      $t->unique(['user_id','product_id']);
    });
  }
  public function down(): void { Schema::dropIfExists('wishlists'); }
};

