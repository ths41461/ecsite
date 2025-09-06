<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_product_metrics_current_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('product_metrics_current', function (Blueprint $t) {
      $t->foreignId('product_id')->primary()->constrained()->cascadeOnDelete();
      $t->integer('units_7d')->default(0);
      $t->integer('units_30d')->default(0);
      $t->decimal('conv_rate_pdp', 5, 4)->default(0);
      $t->decimal('atc_rate', 5, 4)->default(0);
      $t->decimal('search_ctr', 5, 4)->default(0);
      $t->integer('revenue_30d')->default(0);
      $t->integer('wishlist_14d')->default(0);
      $t->decimal('rating_bayes', 5, 4)->default(0);
      $t->decimal('freshness_bonus', 5, 4)->default(0);
      $t->integer('stock')->default(0);
      $t->integer('safety_stock')->default(0);
    });
  }
  public function down(): void { Schema::dropIfExists('product_metrics_current'); }
};

