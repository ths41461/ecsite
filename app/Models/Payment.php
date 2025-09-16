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

            $statusString = (string)($tx['status'] ?? 'pending');
            $legacyStatus = static::legacyStatusFor($statusString);

            $provider = (string)($tx['provider'] ?? 'mock');
            $extId = $tx['ext_id'] ?? null;
            if ($extId) {
                $duplicate = DB::table('payment_transactions')
                    ->where(['provider' => $provider, 'ext_id' => $extId])
                    ->exists();
                if ($duplicate) {
                    $updates = [];
                    if ($toId) {
                        $updates['payment_status_id'] = $toId;
                    }
                    if ($legacyStatus) {
                        $updates['status'] = $legacyStatus;
                    }
                    if ($updates) {
                        $this->forceFill($updates)->save();
                    }
                    return;
                }
            }

            // 2) write transaction row (auditable)
            DB::table('payment_transactions')->insert([
                'payment_id'   => $this->id,
                'provider'     => $provider,
                'ext_id'       => $extId,
                'amount_yen'   => $tx['amount_yen'] ?? null,
                'currency'     => $tx['currency'] ?? 'JPY',
                'status'       => $statusString,
                'payload_json' => !empty($tx['payload']) ? json_encode($tx['payload']) : null,
                'occurred_at'  => $tx['occurred_at'] ?? now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // 3) bump payment status if provided
            $updates = [];
            if ($toId) {
                $updates['payment_status_id'] = $toId;
            }
            if ($legacyStatus) {
                $updates['status'] = $legacyStatus;
            }
            if ($updates) {
                $this->forceFill($updates)->save();
            }
        });
    }

    public static function legacyStatusFor(string $status): ?string
    {
        return match ($status) {
            'authorized', 'captured', 'approved', 'succeeded' => 'approved',
            'failed', 'payment_failed', 'declined' => 'declined',
            'refunded' => 'refunded',
            'void', 'voided' => 'voided',
            'created' => 'created',
            default => null,
        };
    }
}
