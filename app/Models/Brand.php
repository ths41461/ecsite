<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/Brand.php
class Brand extends Model {
  use HasFactory;
  protected $fillable = ['name','slug'];
  public function products(){ return $this->hasMany(Product::class); }
}

