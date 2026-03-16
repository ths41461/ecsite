<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_coupons_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('coupons', function (Blueprint $t) {
      $t->id();
      $t->string('code', 40)->unique();
      $t->string('description', 160)->nullable();
      $t->enum('type', ['percent','fixed']);
      $t->integer('value'); // percent int or fixed yen
      $t->dateTime('starts_at')->nullable();
      $t->dateTime('ends_at')->nullable();
      $t->integer('max_uses')->nullable();
      $t->integer('used_count')->default(0);
      $t->boolean('is_active')->default(true);
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('coupons'); }
};

