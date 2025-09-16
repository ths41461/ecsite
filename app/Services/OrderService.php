<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private CartService $cart)
    {
    }

    /**
     * Create an Order + Payment from current cart. Minimal customer fields for this stage.
     * Returns the persisted Order with items loaded.
     */
    public function createFromCart(string $sessionId, array $customer): Order
    {
        $cart = $this->cart->get($sessionId);

        if (empty($cart['lines'])) {
            throw new \RuntimeException('Cart is empty');
        }

        // Compute a stable digest of the cart (variant_id, qty, price_cents)
        $digest = $this->computeCartDigest($cart);

        // Try to reuse a recent pending order for this session when safe
        $reusable = $this->findReusablePendingOrder($sessionId, $digest, $cart);
        if ($reusable) {
            return $reusable;
        }

        // No reusable order. Proactively expire/cancel any conflicting or expired pending order
        $this->expireOrCancelPendingForSession($sessionId, $digest, $cart);

        $order = DB::transaction(function () use ($cart, $customer, $sessionId, $digest) {
            $order = new Order();
            $order->order_number = $this->generateOrderNumber();
            $order->user_id = $customer['user_id'] ?? null;
            $order->email = $customer['email'] ?? 'guest@example.com';
            $order->name = $customer['name'] ?? 'Guest';
            $order->phone = $customer['phone'] ?? null;
            $order->address_line1 = $customer['address_line1'] ?? 'N/A';
            $order->address_line2 = $customer['address_line2'] ?? null;
            $order->city = $customer['city'] ?? null;
            $order->state = $customer['state'] ?? null;
            $order->zip = $customer['zip'] ?? null;
            $order->cart_session_id = $sessionId;

            // Cart totals are in cents; convert to yen integers
            $subtotalYen = (int) round(($cart['subtotal_cents'] ?? 0) / 100);
            // Combine sale savings and coupon discount
            $discountYen = (int) round((($cart['savings_cents'] ?? 0) + ($cart['coupon_discount_cents'] ?? 0)) / 100);
            $shippingYen = 0; // out of scope for now
            $taxYen = 0;      // out of scope for now
            $totalYen = (int) round(($cart['total_cents'] ?? 0) / 100);

            $order->subtotal_yen = $subtotalYen;
            $order->discount_yen = $discountYen;
            $order->coupon_code = $cart['coupon_code'] ?? null;
            $order->coupon_discount_yen = (int) round((int)($cart['coupon_discount_cents'] ?? 0) / 100);
            $order->shipping_yen = $shippingYen;
            $order->tax_yen = $taxYen;
            $order->total_yen = $totalYen;
            $order->payment_mode = 'stripe';
            $order->status = 'ordered';
            $order->ordered_at = now();
            $order->pending_expires_at = now()->addMinutes((int) config('cart.order_pending_ttl_minutes', 60));
            $order->cart_digest = $digest;
            $order->details_completed_at = null;
            $order->payment_started_at = null;
            // initialize modern status to pending
            $pendingId = (int) DB::table('order_statuses')->where('code', 'pending')->value('id');
            if ($pendingId) {
                $order->order_status_id = $pendingId;
            }
            $order->save();

            // Items
            foreach ($cart['lines'] as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => (int)$line['product']['id'],
                    'product_variant_id' => (int)$line['variant_id'],
                    'name_snapshot' => (string)$line['product']['name'],
                    'sku_snapshot' => (string)$line['sku'],
                    'unit_price_yen' => (int) round(((int)$line['price_cents']) / 100),
                    'qty' => (int)$line['qty'],
                    'line_total_yen' => (int) round(((int)$line['line_total_cents']) / 100),
                ]);
            }

            // Initial payment row (pending)
            $payment = new Payment();
            $payment->order_id = $order->id;
            $payment->provider = 'stripe';
            $payment->type = 'auth';
            $payment->amount_yen = $totalYen;
            $payment->status = 'created';
            $payment->payment_status_id = (int) DB::table('payment_statuses')->where('code', 'pending')->value('id');
            $payment->payload_json = [
                'currency' => 'JPY',
                'cart_session_id' => $sessionId,
                'cart_digest' => $digest,
                'applied_coupon_code' => $cart['coupon_code'] ?? null,
            ];
            $payment->save();

            return $order->fresh(['items', 'payments']);
        });

        return $order;
    }

    public function updateCustomerDetails(Order $order, array $details): Order
    {
        $order->forceFill([
            'email' => $details['email'] ?? $order->email,
            'name' => $details['name'] ?? $order->name,
            'phone' => $details['phone'] ?? $order->phone,
            'address_line1' => $details['address_line1'] ?? $order->address_line1,
            'address_line2' => $details['address_line2'] ?? $order->address_line2,
            'city' => $details['city'] ?? $order->city,
            'state' => $details['state'] ?? $order->state,
            'zip' => $details['zip'] ?? $order->zip,
            'details_completed_at' => now(),
        ])->save();

        return $order->fresh(['items', 'payments']);
    }

    private function generateOrderNumber(): string
    {
        // Simple: yymmdd + random 6
        do {
            $candidate = now()->format('ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (Order::where('order_number', $candidate)->exists());
        return $candidate;
    }

    private function computeCartDigest(array $cart): string
    {
        $norm = [
            'lines' => array_map(function ($l) {
                return [
                    'variant_id' => (int) $l['variant_id'],
                    'qty' => (int) $l['qty'],
                    'price_cents' => (int) $l['price_cents'],
                ];
            }, $cart['lines'] ?? []),
            'subtotal_cents' => (int) ($cart['subtotal_cents'] ?? 0),
            'total_cents' => (int) ($cart['total_cents'] ?? 0),
        ];
        // Sort lines by variant_id for stability
        usort($norm['lines'], fn($a,$b) => $a['variant_id'] <=> $b['variant_id']);
        return hash('sha256', json_encode($norm));
    }

    private function findReusablePendingOrder(string $sessionId, string $digest, array $cart): ?Order
    {
        $pendingId = (int) DB::table('order_statuses')->where('code', 'pending')->value('id');
        if (!$pendingId) return null;

        $candidate = Order::where('cart_session_id', $sessionId)
            ->where('order_status_id', $pendingId)
            ->whereNotNull('pending_expires_at')
            ->where('pending_expires_at', '>', now())
            ->orderByDesc('id')
            ->with('payments')
            ->first();

        if (!$candidate) return null;

        // Must match digest and totals to reuse
        $totalsMatch = (
            (int) $candidate->total_yen === (int) round(((int)($cart['total_cents'] ?? 0))/100)
        );
        if ($candidate->cart_digest !== $digest || !$totalsMatch) {
            return null;
        }

        // Ensure latest payment not processed
        $latestPay = $candidate->payments()->latest()->first();
        if ($latestPay && $latestPay->processed_at) {
            return null;
        }

        return $candidate->fresh(['items','payments']);
    }

    /**
     * Cancels the latest pending order for this session when expired or mismatched.
     * Returns a reason string when a cancellation happened: 'timeout' | 'changed'.
     */
    public function expireOrCancelPendingForSession(string $sessionId, string $digest, array $cart): ?string
    {
        $pendingId = (int) DB::table('order_statuses')->where('code', 'pending')->value('id');
        if (!$pendingId) return null;

        $candidate = Order::where('cart_session_id', $sessionId)
            ->where('order_status_id', $pendingId)
            ->orderByDesc('id')
            ->with('payments')
            ->first();

        if (!$candidate) return null;

        $latestPay = $candidate->payments()->latest()->first();
        if ($latestPay && $latestPay->processed_at) {
            return null; // already processed, don't touch
        }

        $expired = $candidate->pending_expires_at && now()->greaterThanOrEqualTo($candidate->pending_expires_at);
        $totalsMismatch = (
            (int) $candidate->total_yen !== (int) round(((int)($cart['total_cents'] ?? 0))/100)
        );
        $digestMismatch = $candidate->cart_digest !== $digest;

        if (!($expired || $totalsMismatch || $digestMismatch)) {
            return null; // nothing to do
        }

        DB::transaction(function () use ($candidate) {
            // Modern status transition + legacy field/timestamp for visibility
            try { $candidate->transitionTo('cancelled'); } catch (\Throwable $e) {}
            $candidate->forceFill([
                'status' => 'canceled',
                'canceled_at' => now(),
            ])->save();
        });

        return $expired ? 'timeout' : 'changed';
    }

    /**
     * Convenience for GET /checkout to proactively cancel stale pending order and return reason.
     */
    public function checkAndCancelPendingForSessionIfStale(string $sessionId): ?string
    {
        $cart = $this->cart->get($sessionId);
        $digest = $this->computeCartDigest($cart);
        return $this->expireOrCancelPendingForSession($sessionId, $digest, $cart);
    }

    /**
     * Returns a reusable pending order for current session/cart (or null).
     */
    public function getReusablePendingForSession(string $sessionId): ?Order
    {
        $cart = $this->cart->get($sessionId);
        if (empty($cart['lines'])) return null;
        $digest = $this->computeCartDigest($cart);
        return $this->findReusablePendingOrder($sessionId, $digest, $cart);
    }

    /**
     * Cancels the latest pending (unprocessed) order for this session and restores its items to the cart.
     * Returns true if something was cancelled and restored.
     */
    public function cancelPendingAndRestoreCart(string $sessionId): ?Order
    {
        $pendingId = (int) DB::table('order_statuses')->where('code', 'pending')->value('id');
        if (!$pendingId) return null;

        /** @var Order|null $order */
        $order = Order::where('cart_session_id', $sessionId)
            ->where('order_status_id', $pendingId)
            ->orderByDesc('id')
            ->with(['items','payments'])
            ->first();
        if (!$order) return null;

        $latestPay = $order->payments()->latest()->first();
        if ($latestPay && $latestPay->processed_at) return null;

        // Restore items to cart (reset to exact snapshot)
        $this->cart->clear($sessionId);
        foreach ($order->items as $item) {
            $this->cart->add($sessionId, (int)$item->product_variant_id, (int)$item->qty);
        }

        // Cancel the order
        DB::transaction(function () use ($order) {
            try { $order->transitionTo('cancelled'); } catch (\Throwable $e) {}
            $order->forceFill([
                'status' => 'canceled',
                'canceled_at' => now(),
                'pending_expires_at' => now(),
                // ensure reason set for analytics and UX
                'cancel_reason' => $order->cancel_reason ?: 'customer_canceled',
            ])->save();
        });

        return $order->fresh(['items','payments']);
    }

    /**
     * Cancel an order if not already paid/canceled, optionally restoring the cart for a given session id.
     * Idempotent via order state and emailed_at flags.
     */
    public function cancelIfNotPaid(Order $order, string $reason, ?string $cartSessionId = null): Order
    {
        // Paid orders are final; do nothing once a paid status or captured transaction exists
        $paidId = (int) DB::table('order_statuses')->where('code', 'paid')->value('id');
        $payments = $order->relationLoaded('payments') ? $order->payments : $order->payments()->get();
        $paymentIds = $payments->pluck('id');
        $hasCapturedTransaction = $paymentIds->isNotEmpty() && DB::table('payment_transactions')
            ->whereIn('payment_id', $paymentIds)
            ->whereIn('status', ['captured', 'refunded'])
            ->exists();

        if (($paidId && (int)$order->order_status_id === $paidId) || $hasCapturedTransaction) {
            return $order;
        }

        // Already canceled? no-op
        if ($order->status === 'canceled') {
            return $order;
        }

        // Restore cart to the original snapshot for the provided (or stored) session id
        $sid = $cartSessionId ?: (string)($order->cart_session_id ?? '');
        if ($sid !== '') {
            $this->cart->clear($sid);
            foreach ($order->items as $item) {
                $this->cart->add($sid, (int)$item->product_variant_id, (int)$item->qty);
            }
        }

        DB::transaction(function () use ($order, $reason) {
            try { $order->transitionTo('cancelled'); } catch (\Throwable $e) {}
            $order->forceFill([
                'status' => 'canceled',
                'canceled_at' => now(),
                'pending_expires_at' => now(),
                'cancel_reason' => $reason,
            ])->save();
        });

        return $order->fresh(['items','payments']);
    }
}
