<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/OrderItem.php
class OrderItem extends Model {
  use HasFactory;
  protected $fillable = ['order_id','product_id','product_variant_id','name_snapshot','sku_snapshot','unit_price_yen','qty','line_total_yen'];
  public function order(){ return $this->belongsTo(Order::class); }
  public function product(){ return $this->belongsTo(Product::class); }
  public function variant(){ return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
}

