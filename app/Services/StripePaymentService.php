<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class StripePaymentService
{
    private StripeClient $stripe;
    public function __construct(private \App\Services\CartService $cart)
    {
        $this->stripe = new StripeClient(config('stripe.secret'));
    }

    /**
     * Creates a Stripe Checkout Session for the given order and returns it.
     * Also attaches the session id to the order's latest payment payload for traceability.
     */
    public function createCheckoutSession(Order $order, string $cartSessionId): object
    {
        if (!config('stripe.secret')) {
            throw new \RuntimeException('Stripe secret key is missing. Set STRIPE_SECRET in .env');
        }
        $successUrl = url('/checkout/thanks/' . $order->order_number) . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl  = url('/checkout/cancel/' . $order->order_number) . '?session_id={CHECKOUT_SESSION_ID}';
        $returnUrl  = $successUrl; // Embedded Checkout uses return_url after completion

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
        // Determine applied in-app coupon (if any)
        $cart = $this->cart->get($cartSessionId);
        $appliedCouponCode = $cart['coupon_code'] ?? null;
        $appliedCouponDiscountYen = (int) round((int)($cart['coupon_discount_cents'] ?? 0) / 100);

        $params = [
            'mode' => 'payment',
            'ui_mode' => 'embedded',
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            // Embedded Checkout only accepts return_url; do not send success/cancel
            'return_url'  => $returnUrl,
            // Allow Stripe promo codes only when no in-app coupon applied (avoid stacking)
            'allow_promotion_codes' => $appliedCouponDiscountYen <= 0,
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

        // If in-app coupon applied, create a one-time Stripe coupon and attach to session discounts
        if ($appliedCouponDiscountYen > 0) {
            $coupon = $this->stripe->coupons->create([
                'amount_off' => $appliedCouponDiscountYen,
                'currency'   => 'jpy',
                'duration'   => 'once',
                'name'       => 'Order ' . $order->order_number . ' discount',
                'metadata'   => [
                    'order_number' => (string)$order->order_number,
                    'local_coupon_code' => (string)$appliedCouponCode,
                ],
            ], [
                'idempotency_key' => 'coupon:' . $order->order_number . ':' . $appliedCouponDiscountYen,
            ]);
            $params['discounts'] = [['coupon' => $coupon->id]];
        }

        // Add idempotency key to guard against network retries on this attempt
        $idempotency = [
            'idempotency_key' => 'checkout:' . $order->order_number . ':' . bin2hex(random_bytes(8)),
        ];

        try {
            $session = $this->stripe->checkout->sessions->create($params, $idempotency);
        } catch (\Throwable $e) {
            \Log::error('Stripe Checkout Session create failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // Attach session id to payment payload for correlation
        /** @var Payment|null $payment */
        $payment = $order->payments()->latest()->first();
        if ($payment) {
            $payload = $payment->payload_json ?? [];
            $payload['checkout_session_id'] = $session->id;
            if (!empty($appliedCouponCode)) {
                $payload['applied_coupon_code'] = $appliedCouponCode;
                $payload['applied_coupon_discount_yen'] = $appliedCouponDiscountYen;
            }
            $payment->forceFill(['payload_json' => $payload])->save();
        }

        return $session;
    }
}
