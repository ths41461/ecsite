<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_sliders_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('sliders', function (Blueprint $t) {
      $t->id();
      $t->string('image_path', 255);
      $t->string('tagline', 80)->nullable();
      $t->string('title', 120)->nullable();
      $t->string('subtitle', 160)->nullable();
      $t->string('link_url', 255)->nullable();
      $t->boolean('is_active')->default(true);
      $t->dateTime('starts_at')->nullable();
      $t->dateTime('ends_at')->nullable();
      $t->integer('sort')->default(0);
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('sliders'); }
};

