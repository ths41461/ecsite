<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

// app/Models/Order.php
class Order extends Model
{
    use HasFactory;

    protected $casts = [
        'ordered_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'canceled_at' => 'datetime',
        'inventory_decremented_at' => 'datetime',
        'confirmation_emailed_at' => 'datetime',
        'cancellation_emailed_at' => 'datetime',
        'details_completed_at' => 'datetime',
        'payment_started_at' => 'datetime',
    ];

    protected $fillable = [
        'order_number',
        'user_id',
        'email',
        'name',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'zip',
        'subtotal_yen',
        'tax_yen',
        'shipping_yen',
        'discount_yen',
        'total_yen',
        'payment_mode',
        'status',
        'order_status_id',
        'ordered_at',
        'shipped_at',
        'delivered_at',
        'canceled_at',
        'details_completed_at',
        'payment_started_at',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'coupon_code',
        'coupon_discount_yen',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Convenience relation if you ever need the latest payment directly
    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    public function couponRedemption()
    {
        return $this->hasOne(CouponRedemption::class, 'order_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }

    public function getStatusTimelineAttribute()
    {
        return DB::table('order_status_history as h')
            ->join('order_statuses as s', 's.id', '=', 'h.to_status_id')
            ->where('h.order_id', $this->id)
            ->select('s.name as status', 'h.changed_at', 'h.changed_by', 'h.from_status_id', 'h.to_status_id')
            ->orderBy('h.changed_at', 'asc')
            ->get();
    }

    public function transitionTo(int|string $status, ?int $changedBy = null): void
    {
        /** @var \Illuminate\Database\Connection $conn */
        $conn = DB::connection();
        $conn->transaction(function () use ($status, $changedBy) {
            $fromId = (int) $this->order_status_id;

            // Resolve target status id (by code or direct id)
            $toId = is_numeric($status)
                ? (int) $status
                : (int) DB::table('order_statuses')->where('code', $status)->value('id');

            if (!$toId) {
                throw new \InvalidArgumentException('Unknown order status: ' . (string)$status);
            }

            // No-op when transitioning to the same status (defensive against duplicate events)
            if ($toId === $fromId) {
                return;
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
