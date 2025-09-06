<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('product_variants', function (Blueprint $t) {
      $t->id();
      $t->foreignId('product_id')->constrained()->cascadeOnDelete();
      $t->string('sku', 64)->unique();
      $t->json('option_json')->nullable(); // {"volume":"50ml"} など
      $t->integer('price_yen')->unsigned();
      $t->integer('sale_price_yen')->unsigned()->nullable();
      $t->boolean('is_active')->default(true);
      $t->timestamps();
      $t->index(['product_id','is_active']);
    });
  }
  public function down(): void { Schema::dropIfExists('product_variants'); }
};

