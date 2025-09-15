<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class StripePaymentService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('stripe.secret'));
    }

    /**
     * Creates a Stripe Checkout Session for the given order and returns it.
     * Also attaches the session id to the order's latest payment payload for traceability.
     */
    public function createCheckoutSession(Order $order, string $cartSessionId): object
    {
        $successUrl = url('/checkout/thanks/' . $order->order_number) . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl  = url('/checkout/cancel/' . $order->order_number) . '?session_id={CHECKOUT_SESSION_ID}';

        $lineItems = [];
        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $item->name_snapshot,
                    ],
                    // JPY is zero-decimal currency; Stripe expects amounts in yen
                    'unit_amount' => (int)$item->unit_price_yen,
                ],
                'quantity' => (int)$item->qty,
            ];
        }

        // We currently do not add separate shipping/tax lines; totals should still match order->total_yen.
        $params = [
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            // Allow customers to enter promotion codes on Stripe-hosted page
            'allow_promotion_codes' => true,
            // Prefill email when available
            ...(filter_var($order->email, FILTER_VALIDATE_EMAIL) ? ['customer_email' => $order->email] : []),
            // Optional UX improvements
            'billing_address_collection' => 'auto',
            'phone_number_collection' => ['enabled' => false],
            'locale' => 'auto',
            'metadata' => [
                'order_number' => (string) $order->order_number,
                'order_id'     => (string) $order->id,
                'cart_session_id' => (string) $cartSessionId,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'order_number' => (string) $order->order_number,
                    'order_id'     => (string) $order->id,
                    'cart_session_id' => (string) $cartSessionId,
                ],
            ],
        ];

        // Add idempotency key to guard against network retries on this attempt
        $idempotency = [
            'idempotency_key' => 'checkout:' . $order->order_number . ':' . bin2hex(random_bytes(8)),
        ];

        $session = $this->stripe->checkout->sessions->create($params, $idempotency);

        // Attach session id to payment payload for correlation
        /** @var Payment|null $payment */
        $payment = $order->payments()->latest()->first();
        if ($payment) {
            $payload = $payment->payload_json ?? [];
            $payload['checkout_session_id'] = $session->id;
            $payment->forceFill(['payload_json' => $payload])->save();
        }

        return $session;
    }
}
