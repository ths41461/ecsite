<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_path', 'tagline', 'title', 'subtitle',
        'link_url', 'is_active', 'starts_at', 'ends_at', 'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeCurrent($q)
    {
        return $q->where(function ($x) {
            $x->whereNull('starts_at')->orWhere('starts_at', '<=', now());
        })->where(function ($x) {
            $x->whereNull('ends_at')->orWhere('ends_at', '>=', now());
        });
    }
}