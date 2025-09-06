<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/ProductVariant.php
class ProductVariant extends Model {
  use HasFactory;
  protected $casts = ['option_json'=>'array','is_active'=>'boolean'];
  protected $fillable = ['product_id','sku','option_json','price_yen','sale_price_yen','is_active'];
  public function product(){ return $this->belongsTo(Product::class); }
  public function inventory(){ return $this->hasOne(Inventory::class, 'product_variant_id'); }
}

