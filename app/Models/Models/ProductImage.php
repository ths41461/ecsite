<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'path', 'alt', 'sort'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort');
    }
}
