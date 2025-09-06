<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_shipments_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('shipments', function (Blueprint $t) {
      $t->id();
      $t->foreignId('order_id')->constrained()->cascadeOnDelete();
      $t->string('carrier', 60)->nullable();
      $t->string('tracking_number', 80)->nullable()->unique();
      $t->enum('status', ['label','shipped','in_transit','delivered','exception'])->default('label');
      $t->dateTime('shipped_at')->nullable();
      $t->dateTime('delivered_at')->nullable();
      $t->json('timeline_json')->nullable();
      $t->timestamps();
      $t->index(['order_id','status']);
    });
  }
  public function down(): void { Schema::dropIfExists('shipments'); }
};

