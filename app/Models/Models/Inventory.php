<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/Inventory.php
class Inventory extends Model {
  use HasFactory;
  public $timestamps = false; // we manage updated_at manually
  protected $fillable = ['product_variant_id','stock','safety_stock','managed','updated_at'];
  protected $casts = ['managed'=>'boolean','updated_at'=>'datetime'];
  public function variant(){ return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
}

