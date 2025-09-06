<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('brands', function (Blueprint $t) {
      $t->id();
      $t->string('name', 80)->unique();
      $t->string('slug', 90)->unique();
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('brands'); }
};

