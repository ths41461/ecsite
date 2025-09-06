<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/CartItem.php
class CartItem extends Model {
  use HasFactory;
  protected $fillable = ['cart_id','product_variant_id','qty','unit_price_yen'];
  public function cart(){ return $this->belongsTo(Cart::class); }
  public function variant(){ return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
}

