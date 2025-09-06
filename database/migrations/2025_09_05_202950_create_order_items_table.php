<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_order_items_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('order_items', function (Blueprint $t) {
      $t->id();
      $t->foreignId('order_id')->constrained()->cascadeOnDelete();
      $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
      $t->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
      $t->string('name_snapshot', 160);
      $t->string('sku_snapshot', 64)->nullable();
      $t->integer('unit_price_yen');
      $t->integer('qty');
      $t->integer('line_total_yen');
      $t->timestamps();
      $t->index('order_id');
    });
  }
  public function down(): void { Schema::dropIfExists('order_items'); }
};

