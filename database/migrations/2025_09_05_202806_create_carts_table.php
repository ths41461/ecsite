<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('carts', function (Blueprint $t) {
      $t->uuid('id')->primary(); // session carts
      $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('carts'); }
};

