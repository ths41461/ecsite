<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// app/Models/Brand.php
class Brand extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'logo', 'founded', 'founder', 'origin', 'category', 'description'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the name of the brand for audit purposes.
     */
    public function getNameForAudit()
    {
        return $this->name;
    }
}
