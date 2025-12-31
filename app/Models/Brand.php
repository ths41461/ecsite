<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// app/Models/Brand.php
class Brand extends Model {
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'logo'];

    public function products(){
        return $this->hasMany(Product::class);
    }
}

