<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/CartItem.php
class CartItem extends Model {
  use HasFactory;
  protected $fillable = ['cart_id','product_variant_id','qty','unit_price_yen','line_total_yen'];
  public function cart(){ return $this->belongsTo(Cart::class); }
  public function variant(){ return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }

  public function getLineTotalYenAttribute(): int
  {
      return $this->qty * $this->unit_price_yen;
  }

  protected static function booted(): void
  {
      static::creating(function ($cartItem) {
          $cartItem->line_total_yen = $cartItem->qty * $cartItem->unit_price_yen;
      });

      static::updating(function ($cartItem) {
          $cartItem->line_total_yen = $cartItem->qty * $cartItem->unit_price_yen;
      });
  }
}

