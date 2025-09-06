<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/Review.php
class Review extends Model {
  use HasFactory;
  protected $fillable = ['product_id','user_id','rating','body','approved'];
  protected $casts = ['approved'=>'boolean'];
  public function product(){ return $this->belongsTo(Product::class); }
  public function user(){ return $this->belongsTo(User::class); }
}

