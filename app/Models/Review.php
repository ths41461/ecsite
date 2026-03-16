<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'body',
        'approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'approved' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the product that owns the review.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user that owns the review.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the name of the review for audit purposes.
     */
    public function getNameForAudit()
    {
        return 'Review by ' . ($this->user->name ?? 'Unknown') . ' for ' . ($this->product->name ?? 'Product #' . $this->product_id);
    }
}