<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\StripeClient;
use App\Mail\RefundIssuedMail;

class StripeRefundService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('stripe.secret'));
    }

    /**
     * Creates a refund on Stripe for the latest captured charge of the given payment.
     * Returns array{id:string, amount:int, status:string}
     */
    public function createRefund(Payment $payment, int $amountYen, ?string $reason = null, array $metadata = []): array
    {
        // Find the captured PI id from transactions
        $piId = DB::table('payment_transactions')
            ->where('payment_id', $payment->id)
            ->where('status', 'captured')
            ->orderByDesc('id')
            ->value('ext_id');
        if (!$piId) {
            throw new \RuntimeException('No captured payment intent found for this payment');
        }

        $pi = $this->stripe->paymentIntents->retrieve($piId, ['expand' => ['charges']]);
        $chargeId = $pi->latest_charge ?? null;
        if (!$chargeId && isset($pi->charges) && !empty($pi->charges->data)) {
            $chargeId = $pi->charges->data[0]->id;
        }
        if (!$chargeId) {
            throw new \RuntimeException('No charge found to refund');
        }

        $order = $payment->order()->first();
        $md = array_merge([
            'order_number' => $order?->order_number,
            'payment_id' => (string)$payment->id,
        ], $metadata);

        $params = [
            'charge' => $chargeId,
            'amount' => $amountYen,
            'metadata' => $md,
        ];
        if ($reason) {
            // Stripe accepts limited reason values; we still pass our reason via metadata
            $params['reason'] = in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'], true)
                ? $reason
                : 'requested_by_customer';
        }

        $refund = $this->stripe->refunds->create($params);

        return [
            'id' => $refund->id,
            'amount' => (int) $refund->amount,
            'status' => (string) $refund->status,
        ];
    }

    /**
     * Records a refund coming from webhook or after createRefund.
     * Idempotent via (provider, ext_id) unique constraint.
     */
    public function recordRefundWebhook(array $refund, Payment $payment, ?string $orderReason = null): void
    {
        /** @var Order $order */
        $order = $payment->order()->firstOrFail();

        DB::beginTransaction();
        try {
            // 1) write transaction (dedup by unique idx)
            DB::table('payment_transactions')->insert([
                'payment_id'   => $payment->id,
                'provider'     => 'stripe',
                'ext_id'       => $refund['id'],
                'amount_yen'   => $refund['amount'],
                'currency'     => 'JPY',
                'status'       => ($refund['status'] ?? 'succeeded') === 'succeeded' ? 'refunded' : (string)($refund['status'] ?? 'pending'),
                'payload_json' => json_encode($refund),
                'occurred_at'  => now(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            // Duplicate or any DB error: if duplicate, treat idempotent
            DB::rollBack();
            return;
        }

        // 2) bump aggregates
        DB::table('payments')->where('id', $payment->id)->update([
            'refunded_yen' => DB::raw('LEAST(amount_yen, COALESCE(refunded_yen,0) + ' . (int)$refund['amount'] . ')'),
            'updated_at' => now(),
        ]);

        $newRefundTotal = (int) ($order->refund_total_yen ?? 0) + (int) $refund['amount'];
        $updates = [
            'refund_total_yen' => $newRefundTotal,
            'refund_reason' => $orderReason ?: ($refund['reason'] ?? $order->refund_reason),
            'updated_at' => now(),
        ];
        $isFull = $newRefundTotal >= (int) $order->total_yen;
        if ($isFull) {
            $updates['refunded_at'] = now();
        }
        DB::table('orders')->where('id', $order->id)->update($updates);

        // 3) transition order status (modern + legacy mirror)
        $order->refresh();
        if ($isFull) {
            try {
                $order->transitionTo('refunded');
            } catch (\Throwable $e) {
            }
            $order->forceFill(['status' => 'refunded'])->save();
        } else {
            // keep modern status paid; legacy mirror stays 'processing'; UI shows partial refund badge
        }

        DB::commit();

        // 4) send refund email (idempotent per refund id because insert above is unique). Queue after commit.
        try {
            $orderRefreshed = Order::find($order->id);
            Mail::to($orderRefreshed->email)->queue(new RefundIssuedMail($orderRefreshed->loadMissing('items'), (int)$refund['amount']));
        } catch (\Throwable $e) {
            Log::error('Refund email failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
