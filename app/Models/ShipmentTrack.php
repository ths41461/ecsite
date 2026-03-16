<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentTrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'carrier',
        'track_no',
        'status',
        'raw_event_json',
        'event_time',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'raw_event_json' => 'array',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get a human-readable description of the tracking event
     */
    public function getDescriptionAttribute(): string
    {
        $descriptions = [
            'packed' => 'Package has been packed and is ready for shipment',
            'in_transit' => 'Package is in transit to destination',
            'delivered' => 'Package has been delivered',
            'returned' => 'Package has been returned to sender',
        ];

        return $descriptions[$this->status] ?? 'Shipment status updated';
    }
}