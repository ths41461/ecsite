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

        $type = $event->type ?? '';
        $data = $event->data['object'] ?? null;

        try {
            match ($type) {
                'checkout.session.completed' => $this->onCheckoutCompleted($data),
                'payment_intent.succeeded'   => $this->onPaymentIntentSucceeded($data),
                'payment_intent.payment_failed' => $this->onPaymentIntentFailed($data),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response('Webhook processing error', 500);
        }

        return response('ok');
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
            $payment->recordTransaction([
                'provider' => 'stripe',
                'ext_id' => $session->payment_intent ?? null,
                'amount_yen' => $order->total_yen,
                'currency' => 'JPY',
                'status' => 'authorized', // Checkout completed; capture status may come via PI event
                'payload' => $session,
                'occurred_at' => now(),
            ]);
            $payment->forceFill(['processed_at' => now()])->save();
        }

        // Move order to paid state
        // Move order to a processing-like state while we await PI succeeded; if missing lookups, ignore
        try { $order->transitionTo('paid'); } catch (\Throwable $e) {}
        $order->forceFill(['status' => 'processing'])->save();
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
                'amount_yen' => $order->total_yen,
                'currency' => strtoupper($pi->currency ?? 'jpy'),
                'status' => 'captured',
                'payload' => $pi,
                'occurred_at' => now(),
            ]);
            $payment->forceFill(['processed_at' => now()])->save();
        }

        try { $order->transitionTo('paid'); } catch (\Throwable $e) {}
        // Mirror legacy enum-ish status to an appropriate state (processing is the closest to paid)
        $order->forceFill(['status' => 'processing'])->save();

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

        // Optionally clear the cart for the session that created this order
        $sid = $pi->metadata->cart_session_id ?? null;
        if ($sid && config('cart.clear_on_payment_success')) {
            try { $this->cart->clear((string)$sid); } catch (\Throwable $e) {}
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
            $payment->recordTransaction([
                'provider' => 'stripe',
                'ext_id' => $pi->id,
                'amount_yen' => $order->total_yen,
                'currency' => strtoupper($pi->currency ?? 'jpy'),
                'status' => 'failed',
                'payload' => $pi,
                'occurred_at' => now(),
            ]);
            $payment->forceFill(['processed_at' => now()])->save();
        }
    }
}
