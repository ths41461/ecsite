<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('categories', function (Blueprint $t) {
      $t->id();
      $t->string('name', 80);
      $t->string('slug', 90)->unique();
      $t->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
      $t->timestamps();
      $t->index('parent_id');
    });
  }
  public function down(): void { Schema::dropIfExists('categories'); }
};

