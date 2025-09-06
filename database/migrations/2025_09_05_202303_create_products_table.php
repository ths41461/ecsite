<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_products_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('products', function (Blueprint $t) {
      $t->id();
      $t->string('name', 120);
      $t->string('slug', 140)->unique();
      $t->foreignId('brand_id')->constrained()->cascadeOnDelete();
      $t->foreignId('category_id')->constrained()->cascadeOnDelete();
      $t->text('short_desc')->nullable();
      $t->mediumText('long_desc')->nullable();
      $t->boolean('is_active')->default(true);
      $t->boolean('featured')->default(false);
      $t->json('attributes_json')->nullable(); // fragrance notes, radar, etc.
      $t->json('meta_json')->nullable();       // seo, flags
      $t->dateTime('published_at')->nullable();
      $t->timestamps();
      $t->index(['brand_id']);
      $t->index(['category_id']);
      $t->index(['is_active','featured']);
    });
  }
  public function down(): void { Schema::dropIfExists('products'); }
};

