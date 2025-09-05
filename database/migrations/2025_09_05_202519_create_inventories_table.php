<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('inventories', function (Blueprint $t) {
      $t->id();
      $t->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
      $t->integer('stock')->default(0);
      $t->integer('safety_stock')->default(0);
      $t->boolean('managed')->default(true);
      $t->timestamp('updated_at')->useCurrent();
      $t->unique('product_variant_id');
    });
  }
  public function down(): void { Schema::dropIfExists('inventories'); }
};

