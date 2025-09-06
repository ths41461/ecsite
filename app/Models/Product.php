<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// app/Models/Product.php
use Laravel\Scout\Searchable;


class Product extends Model {
  use HasFactory, Searchable;
  protected $casts = ['attributes_json'=>'array','meta_json'=>'array','published_at'=>'datetime','is_active'=>'boolean','featured'=>'boolean'];
  protected $fillable = ['name','slug','brand_id','category_id','short_desc','long_desc','is_active','featured','attributes_json','meta_json','published_at'];
  public function brand(){ return $this->belongsTo(Brand::class); }
  public function category(){ return $this->belongsTo(Category::class); }
  public function images(){ return $this->hasMany(ProductImage::class); }
  public function variants(){ return $this->hasMany(ProductVariant::class); }
  public function toSearchableArray(): array {
    return [
      'id'=>$this->id,
      'name'=>$this->name,
      'brand'=>$this->brand?->name,
      'category'=>$this->category?->name,
      'description'=>trim(($this->short_desc ?? '').' '.($this->long_desc ?? '')),
    ];
  }
}

