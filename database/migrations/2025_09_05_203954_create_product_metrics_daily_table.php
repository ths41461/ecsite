<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_product_metrics_daily_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('product_metrics_daily', function (Blueprint $t) {
      $t->foreignId('product_id')->constrained()->cascadeOnDelete();
      $t->date('date');
      $t->integer('views')->default(0);
      $t->integer('atc')->default(0);
      $t->integer('orders')->default(0);
      $t->integer('revenue_yen')->default(0);
      $t->integer('search_impr')->default(0);
      $t->integer('search_clicks')->default(0);
      $t->integer('wishlist_adds')->default(0);
      $t->decimal('rating_avg', 3, 2)->nullable();
      $t->integer('rating_count')->default(0);
      $t->primary(['product_id','date']);
    });
  }
  public function down(): void { Schema::dropIfExists('product_metrics_daily'); }
};

