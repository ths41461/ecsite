<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ..._create_ranking_snapshots_table.php
return new class extends Migration {
  public function up(): void {
    Schema::create('ranking_snapshots', function (Blueprint $t) {
      $t->id();
      $t->string('scope', 40); // overall | category:{id}
      $t->tinyInteger('rank'); // 1..10
      $t->foreignId('product_id')->constrained()->cascadeOnDelete();
      $t->decimal('score', 10, 6);
      $t->dateTime('computed_at');
      $t->timestamps();
      $t->unique(['scope','rank','computed_at']);
    });
  }
  public function down(): void { Schema::dropIfExists('ranking_snapshots'); }
};

