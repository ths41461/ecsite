<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_orders_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('orders', function (Blueprint $t) {
      $t->id();
      $t->string('order_number', 24)->unique();
      $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $t->string('email', 120);
      $t->string('name', 120);
      $t->string('phone', 30)->nullable();
      $t->string('address_line1', 160);
      $t->string('address_line2', 160)->nullable();
      $t->string('city', 100)->nullable();
      $t->string('state', 100)->nullable();
      $t->string('zip', 20)->nullable();
      $t->integer('subtotal_yen');
      $t->integer('tax_yen')->default(0);
      $t->integer('shipping_yen')->default(0);
      $t->integer('discount_yen')->default(0);
      $t->integer('total_yen');
      $t->enum('payment_mode', ['mock','stripe'])->default('mock');
      $t->enum('status', ['ordered','processing','shipped','delivered','canceled','refunded'])->default('ordered');
      $t->dateTime('ordered_at');
      $t->dateTime('shipped_at')->nullable();
      $t->dateTime('delivered_at')->nullable();
      $t->dateTime('canceled_at')->nullable();
      $t->timestamps();
      $t->index(['status','ordered_at']);
    });
  }
  public function down(): void { Schema::dropIfExists('orders'); }
};

