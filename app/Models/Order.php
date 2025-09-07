<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

// app/Models/Order.php
class Order extends Model
{
    use HasFactory;
    protected $casts = ['ordered_at' => 'datetime', 'shipped_at' => 'datetime', 'delivered_at' => 'datetime', 'canceled_at' => 'datetime'];
    protected $fillable = ['order_number', 'user_id', 'email', 'name', 'phone', 'address_line1', 'address_line2', 'city', 'state', 'zip', 'subtotal_yen', 'tax_yen', 'shipping_yen', 'discount_yen', 'total_yen', 'payment_mode', 'status', 'ordered_at', 'shipped_at', 'delivered_at', 'canceled_at'];
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transitionTo(int|string $status, ?int $changedBy = null): void
    {
        /** @var \Illuminate\Database\Connection $conn */
        $conn = DB::connection();
        $conn->transaction(function () use ($status, $changedBy) {
            $fromId = $this->order_status_id;

            // Resolve target status id (by code or direct id)
            $toId = is_numeric($status)
                ? (int) $status
                : (int) DB::table('order_statuses')->where('code', $status)->value('id');

            if (!$toId) {
                throw new \InvalidArgumentException('Unknown order status: ' . (string)$status);
            }

            // Write history first
            DB::table('order_status_history')->insert([
                'order_id'       => $this->id,
                'from_status_id' => $fromId,
                'to_status_id'   => $toId,
                'changed_by'     => $changedBy,
                'changed_at'     => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Update the order
            $this->forceFill(['order_status_id' => $toId])->save();
        });
    }
}

