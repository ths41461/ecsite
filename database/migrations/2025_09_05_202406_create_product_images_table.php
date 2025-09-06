<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('product_images', function (Blueprint $t) {
      $t->id();
      $t->foreignId('product_id')->constrained()->cascadeOnDelete();
      $t->string('path', 255);
      $t->string('alt', 150)->nullable();
      $t->integer('sort')->default(0);
      $t->timestamps();
      $t->index(['product_id','sort']);
    });
  }
  public function down(): void { Schema::dropIfExists('product_images'); }
};

