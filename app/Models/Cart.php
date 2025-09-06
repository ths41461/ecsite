<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/Cart.php
class Cart extends Model {
  use HasFactory;
  public $incrementing = false; protected $keyType = 'string';
  protected $fillable = ['id','user_id'];
  public function items(){ return $this->hasMany(CartItem::class); }
  public function user(){ return $this->belongsTo(User::class); }
}

