<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/Wishlist.php
class Wishlist extends Model {
  use HasFactory;
  public $timestamps = false;
  protected $fillable = ['user_id','product_id','created_at'];
  public function user(){ return $this->belongsTo(User::class); }
  public function product(){ return $this->belongsTo(Product::class); }
}

