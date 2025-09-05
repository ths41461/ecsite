<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_payments_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('payments', function (Blueprint $t) {
      $t->id();
      $t->foreignId('order_id')->constrained()->cascadeOnDelete();
      $t->string('provider', 20)->default('mock');
      $t->enum('type', ['auth','capture','refund','void'])->default('auth');
      $t->integer('amount_yen');
      $t->enum('status', ['created','approved','declined','refunded','voided'])->default('created');
      $t->json('payload_json')->nullable();
      $t->dateTime('processed_at')->nullable();
      $t->timestamps();
      $t->index(['order_id','status']);
    });
  }
  public function down(): void { Schema::dropIfExists('payments'); }
};

