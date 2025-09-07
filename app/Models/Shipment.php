<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'carrier',
        'tracking_number',
        'status',
        'shipped_at',
        'delivered_at',
        'timeline_json',
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

    public function addTrack(array $event, ?bool $advanceStatus = null): void
    {
        /** @var \Illuminate\Database\Connection $conn */
        $conn = DB::connection();
        $conn->transaction(function () use ($event, $advanceStatus) {
            // 1) Insert track row
            DB::table('shipment_tracks')->insert([
                'shipment_id'   => $this->id,
                'carrier'       => (string)($event['carrier'] ?? 'unknown'),
                'track_no'      => $event['track_no'] ?? null,
                'status'        => (string)($event['status'] ?? 'in_transit'),
                'raw_event_json' => !empty($event['payload']) ? json_encode($event['payload']) : null,
                'event_time'    => $event['event_time'] ?? now(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // 2) Optionally move shipment status (simple heuristic)
            if ($advanceStatus !== false && !empty($event['status'])) {
                $map = [
                    'packed'     => 'packed',
                    'in_transit' => 'in_transit',
                    'delivered'  => 'delivered',
                    'returned'   => 'returned',
                ];
                $code = $map[$event['status']] ?? null;
                if ($code) {
                    $toId = (int) DB::table('shipment_statuses')->where('code', $code)->value('id');
                    if ($toId && $toId !== (int)$this->shipment_status_id) {
                        $this->forceFill(['shipment_status_id' => $toId])->save();
                    }
                }
            }
        });
    }
}
