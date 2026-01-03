<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'order_status_id');
    }

    public function fromStatusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class, 'from_status_id');
    }

    public function toStatusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class, 'to_status_id');
    }
}