<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// app/Models/Category.php
class Category extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = ['name', 'slug', 'parent_id', 'description', 'logo'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all descendants of this category (children, grandchildren, etc.)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get the full path of the category (breadcrumb trail)
     */
    public function getPathAttribute()
    {
        $path = [];
        $category = $this;

        while ($category) {
            array_unshift($path, $category->name);
            $category = $category->parent;
        }

        return implode(' > ', $path);
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_categories');
    }

    /**
     * Get the name of the category for audit purposes.
     */
    public function getNameForAudit()
    {
        return $this->name;
    }
}
