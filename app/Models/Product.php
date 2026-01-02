<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, Searchable, Auditable;

    protected $fillable = [
        'name',
        'slug',
        'brand_id',
        'category_id',
        'short_desc',
        'long_desc',
        'is_active',
        'featured',
        'attributes_json',
        'meta_json',
        'published_at',
    ];

    protected $casts = [
        'attributes_json' => 'array',
        'meta_json'       => 'array',
        'published_at'    => 'datetime',
        'is_active'       => 'boolean',
        'featured'        => 'boolean',
    ];

    /**
     * Set the product's name and automatically generate the slug.
     *
     * @param  string  $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;

        // Only auto-generate slug if it's not already set or if the name has changed and slug is empty
        if (empty($this->attributes['slug']) || $this->isDirty('name')) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    /** Route model binding uses slug instead of id */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Quick helper for readability in controllers/repos */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    // --- Relations ---
    public function brand()
    {
        return $this->belongsTo(\App\Models\Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_products');
    }

    // --- Scout (search) payload ---
    public function toSearchableArray(): array
    {
        // Get variant options for filtering
        $variants = $this->variants->where('is_active', true);
        $genders = $variants->pluck('option_json.gender')->unique()->filter()->values()->all();
        $sizes = $variants->pluck('option_json.size_ml')->unique()->filter()->values()->all();
        
        // Calculate average rating from approved reviews
        $averageRating = $this->reviews()->where('approved', true)->avg('rating') ?? 0;
        
        return [
            'id'        => $this->id,
            'slug'      => $this->slug,
            'name'      => $this->name,
            'brand'     => $this->brand?->name,
            'category'  => $this->category?->name,
            'description' => trim(($this->short_desc ?? '') . ' ' . ($this->long_desc ?? '')),
            'gender'    => $genders, // Array of genders from variants
            'size_ml'   => $sizes,   // Array of sizes from variants
            'rating'    => round($averageRating, 2),
            'review_count' => $this->reviews()->where('approved', true)->count(),
        ];
    }

    public function heroImage()
    {
        return $this->hasOne(\App\Models\ProductImage::class)->where('is_hero', true)->orderBy('rank');
    }
    
    /**
     * Get the reviews for the product.
     */
    public function reviews()
    {
        return $this->hasMany(\App\Models\Review::class);
    }
    
    /**
     * Get the average rating for the product.
     */
    public function averageRating()
    {
        return $this->reviews()->where('approved', true)->avg('rating');
    }

    /**
     * Get the review count for the product.
     */
    public function reviewCount()
    {
        return $this->reviews()->where('approved', true)->count();
    }

    /**
     * Get the events for the product.
     */
    public function events()
    {
        return $this->hasMany(\App\Models\Event::class);
    }

    /**
     * Get the name of the product for audit purposes.
     */
    public function getNameForAudit()
    {
        return $this->name;
    }
}
