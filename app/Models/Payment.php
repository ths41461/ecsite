<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'type',
        'amount_yen',
        'status',
        'payload_json',
        'processed_at',
    ];

    protected $casts = [
        'amount_yen'   => 'integer',
        'processed_at' => 'datetime',
        'payload_json' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function recordTransaction(array $tx): void
    {
        /** @var \Illuminate\Database\Connection $conn */
        $conn = DB::connection();

        $conn->transaction(function () use ($tx) {
            // 1) resolve next status id (optional; keep current if none given)
            $toId = null;
            if (!empty($tx['status'])) {
                $toId = is_numeric($tx['status'])
                    ? (int) $tx['status']
                    : (int) DB::table('payment_statuses')->where('code', $tx['status'])->value('id');

                if (!$toId) {
                    throw new \InvalidArgumentException('Unknown payment status: ' . (string)$tx['status']);
                }
            }

            // 2) write transaction row (auditable)
            DB::table('payment_transactions')->insert([
                'payment_id'   => $this->id,
                'provider'     => (string)($tx['provider'] ?? 'mock'),
                'ext_id'       => $tx['ext_id'] ?? null,
                'amount_yen'   => $tx['amount_yen'] ?? null,
                'currency'     => $tx['currency'] ?? 'JPY',
                'status'       => (string)($tx['status'] ?? 'pending'),
                'payload_json' => !empty($tx['payload']) ? json_encode($tx['payload']) : null,
                'occurred_at'  => $tx['occurred_at'] ?? now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // 3) bump payment status if provided
            if ($toId) {
                $this->forceFill(['payment_status_id' => $toId])->save();
            }
        });
    }
}
