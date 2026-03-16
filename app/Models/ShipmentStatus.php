<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'shipment_status_id');
    }
}