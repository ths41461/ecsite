<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'carrier', 'tracking_number', 'status',
        'shipped_at', 'delivered_at', 'timeline_json',
    ];

    protected $casts = [
        'shipped_at'    => 'datetime',
        'delivered_at'  => 'datetime',
        'timeline_json' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeActive($q)
    {
        return $q->whereIn('status', ['label', 'shipped', 'in_transit']);
    }
}
