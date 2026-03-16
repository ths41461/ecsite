<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_cart_items_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('cart_items', function (Blueprint $t) {
      $t->id();
      $t->uuid('cart_id');
      $t->foreign('cart_id')->references('id')->on('carts')->cascadeOnDelete();
      $t->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
      $t->integer('qty');
      $t->integer('unit_price_yen');
      $t->timestamps();
      $t->unique(['cart_id','product_variant_id']);
    });
  }
  public function down(): void { Schema::dropIfExists('cart_items'); }
};

