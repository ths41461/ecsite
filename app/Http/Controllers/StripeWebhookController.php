<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Stripe\Webhook;
use Stripe\StripeClient;

use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmedMail;

class StripeWebhookController extends Controller
{
    public function __construct(private CartService $cart, private InventoryService $inventory) {}
    public function handle(Request $request)
    {
        $signature = $request->header('Stripe-Signature');
        $payload = $request->getContent();

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('stripe.webhook_secret')
            );
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->processStripeEvent($event);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response('Webhook processing error', 500);
        }

        return response('ok');
    }

    public function processStripeEvent(object $event): void
    {
        $type = $event->type ?? '';
        $data = $event->data['object'] ?? null;

        match ($type) {
            'checkout.session.completed' => $this->onCheckoutCompleted($data),
            'checkout.session.expired'   => $this->onCheckoutExpired($data),
            'payment_intent.succeeded'   => $this->onPaymentIntentSucceeded($data),
            'payment_intent.payment_failed' => $this->onPaymentIntentFailed($data),
            'payment_intent.canceled'    => $this->onPaymentIntentCanceled($data),
            default => null,
        };
    }

    private function onCheckoutCompleted(object $session): void
    {
        $orderNumber = $session->metadata->order_number ?? null;
        if (!$orderNumber) return;

        /** @var Order|null $order */
        $order = Order::where('order_number', $orderNumber)->with('payments')->first();
        if (!$order) return;

        /** @var Payment|null $payment */
        $payment = $order->payments()->latest()->first();
        if ($payment) {
            $piId = $session->payment_intent ?? null;
            $already = $piId
                ? DB::table('payment_transactions')->where([
                    'provider' => 'stripe',
                    'ext_id' => (string)$piId,
                ])->exists()
                : false;
            if (!$already) {
                $payment->recordTransaction([
                    'provider' => 'stripe',
                    'ext_id' => $piId,
                    'amount_yen' => (int) ($session->amount_total ?? $order->total_yen),
                    'currency' => 'JPY',
                    'status' => 'authorized',
                    'payload' => $session,
                    'occurred_at' => now(),
                ]);
            }
            $legacy = Payment::legacyStatusFor('authorized');
            $updates = ['processed_at' => now()];
            if ($legacy) {
                $updates['status'] = $legacy;
            }
            $payment->forceFill($updates)->save();

            // Persist discount/totals from Checkout Session to keep our Order in sync
            try {
                $amountTotal = isset($session->amount_total) ? (int)$session->amount_total : null;
                $amountDiscount = null;
                if (isset($session->total_details) && is_object($session->total_details)) {
                    $amountDiscount = isset($session->total_details->amount_discount)
                        ? (int)$session->total_details->amount_discount
                        : null;
                }

                $updates = [];
                if (!is_null($amountTotal) && $amountTotal > 0) {
                    $updates['total_yen'] = (int)$amountTotal;
                }
                if (!is_null($amountDiscount)) {
                    // Preserve the sale-savings portion; update coupon portion from Stripe
                    $saleSavings = (int)$order->discount_yen - (int)($order->coupon_discount_yen ?? 0);
                    $updates['coupon_discount_yen'] = (int) $amountDiscount;
                    $updates['discount_yen'] = max(0, $saleSavings) + (int)$amountDiscount;
                }
                if ($updates) {
                    $order->forceFill($updates)->save();
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to reconcile discount from Checkout Session', [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Only mark processing while waiting for payment; keep terminal states intact
        if (!in_array($order->status, ['paid', 'shipped', 'delivered', 'refunded'])) {
            $order->forceFill([
                'status' => 'processing',
                'stripe_checkout_session_id' => $session->id ?? $order->stripe_checkout_session_id,
            ])->save();
        } elseif (!empty($session->id) && !$order->stripe_checkout_session_id) {
            $order->forceFill(['stripe_checkout_session_id' => $session->id])->save();
        }
    }

    private function onPaymentIntentSucceeded(object $pi): void
    {
        $orderNumber = $pi->metadata->order_number ?? null;
        if (!$orderNumber) return;
        /** @var Order|null $order */
        $order = Order::where('order_number', $orderNumber)->with('payments')->first();
        if (!$order) return;
        /** @var Payment|null $payment */
        $payment = $order->payments()->latest()->first();
        if ($payment) {
            $payment->recordTransaction([
                'provider' => 'stripe',
                'ext_id' => $pi->id,
                // Use captured amount from PI if available
                'amount_yen' => (int) ($pi->amount_received ?? $pi->amount ?? $order->total_yen),
                'currency' => strtoupper($pi->currency ?? 'jpy'),
                'status' => 'captured',
                'payload' => $pi,
                'occurred_at' => now(),
            ]);
            $legacy = Payment::legacyStatusFor('captured');
            $updates = ['processed_at' => now()];
            if ($legacy) {
                $updates['status'] = $legacy;
            }
            $payment->forceFill($updates)->save();

            // If an in-app coupon was applied, record redemption once per order
            try {
                $payload = $payment->payload_json ?? [];
                $code = isset($payload['applied_coupon_code']) ? (string)$payload['applied_coupon_code'] : null;
                if ($code) {
                    /** @var \App\Models\Coupon|null $coupon */
                    $coupon = \App\Models\Coupon::whereRaw('UPPER(code) = ?', [strtoupper($code)])->first();
                    if ($coupon) {
                        $coupon->redeem($order->user_id ? (int)$order->user_id : null, (int)$order->id);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Coupon redemption failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        // Transition to paid only if not already in paid state
        try {
            $paidId = (int) DB::table('order_statuses')->where('code', 'paid')->value('id');
            if ($paidId && (int) $order->order_status_id !== $paidId) {
                $order->transitionTo('paid');
            }
        } catch (\Throwable $e) {}
        // Mirror legacy enum field to paid as well
        $order->forceFill([
            'status' => 'paid',
            'stripe_payment_intent_id' => $pi->id,
        ])->save();

        // Decrement inventory once per order (idempotent by timestamp)
        if (empty($order->inventory_decremented_at)) {
            try {
                $order->loadMissing('items');
                $this->inventory->decrementForOrder($order);
            } catch (\Throwable $e) {
                Log::error('Inventory decrement failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        // Send confirmation email once per order (DB flag)
        if (empty($order->confirmation_emailed_at)) {
            try {
                Mail::to($order->email)->queue(new OrderConfirmedMail($order->fresh(['items'])));
                $order->forceFill(['confirmation_emailed_at' => now()])->save();
            } catch (\Throwable $e) {
                Log::error('Order confirmation email failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        // Clear the appropriate cart based on whether the order was created by an authenticated user
        if (config('cart.clear_on_payment_success')) {
            // Determine which cart to clear based on the order's user_id
            if ($order->user_id) {
                // This was an order created by an authenticated user - clear the user-based cart
                try {
                    $this->cart->clear('', (int)$order->user_id);
                    Log::info('Cleared user cart after payment', [
                        'order_id' => $order->id,
                        'user_id' => $order->user_id
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to clear user cart after payment', [
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                // This was an order created by a guest - clear the session-based cart
                $cartSessionId = $pi->metadata->cart_session_id ?? null;
                if ($cartSessionId) {
                    try {
                        $this->cart->clear((string)$cartSessionId);
                        Log::info('Cleared session cart after payment', [
                            'order_id' => $order->id,
                            'session_id' => $cartSessionId
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to clear session cart after payment', [
                            'order_id' => $order->id,
                            'session_id' => $cartSessionId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
    }

    private function onPaymentIntentFailed(object $pi): void
    {
        $orderNumber = $pi->metadata->order_number ?? null;
        if (!$orderNumber) return;
        /** @var Order|null $order */
        $order = Order::where('order_number', $orderNumber)->with('payments')->first();
        if (!$order) return;
        /** @var Payment|null $payment */
        $payment = $order->payments()->latest()->first();
        if ($payment) {
            try {
                $payment->recordTransaction([
                    'provider' => 'stripe',
                    'ext_id' => $pi->id,
                    'amount_yen' => $order->total_yen,
                    'currency' => strtoupper($pi->currency ?? 'jpy'),
                    'status' => 'failed',
                    'payload' => $pi,
                    'occurred_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // duplicate, ignore
            }
            $legacy = Payment::legacyStatusFor('failed');
            $updates = ['processed_at' => now()];
            if ($legacy) {
                $updates['status'] = $legacy;
            }
            $payment->forceFill($updates)->save();
        }
    }

    private function onCheckoutExpired(object $session): void
    {
        $orderNumber = $session->metadata->order_number ?? null;
        if (!$orderNumber) return;
        /** @var Order|null $order */
        $order = Order::where('order_number', $orderNumber)->with(['items','payments'])->first();
        if (!$order) return;
        $sid = $session->metadata->cart_session_id ?? null;
        app(\App\Services\OrderService::class)->cancelIfNotPaid($order, 'expired', $sid);
    }

    private function onPaymentIntentCanceled(object $pi): void
    {
        $orderNumber = $pi->metadata->order_number ?? null;
        if (!$orderNumber) return;
        /** @var Order|null $order */
        $order = Order::where('order_number', $orderNumber)->with(['items','payments'])->first();
        if (!$order) return;
        $sid = $pi->metadata->cart_session_id ?? null;
        app(\App\Services\OrderService::class)->cancelIfNotPaid($order, 'psp_canceled', $sid);
    }
}
