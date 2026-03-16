<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\StripePaymentService;
use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderCanceledMail;

class CheckoutController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private StripePaymentService $stripe,
        private CartService $cart,
    ) {}

    /**
     * GET /checkout
     * Simple start page with a button to proceed to payment.
     */
    public function index(Request $request)
    {
        $sessionId = $request->session()->getId();
        $reason = $this->orders->checkAndCancelPendingForSessionIfStale($sessionId);
        $reusable = $this->orders->getReusablePendingForSession($sessionId);
        if (!$reason) {
            $reason = $this->deriveLastAttemptReason($sessionId);
        }

        $userId = $request->user()?->id;
        $cart = $this->cart->get($sessionId, $userId);
        $order = $reusable ? $reusable->loadMissing('items') : null;

        $savedContact = null;
        if ($user = $request->user()) {
            $savedContact = [
                'email' => $user->email,
                'name' => $user->name,
            ];
        }

        return Inertia::render('Checkout/Wizard', [
            'step' => 'review',
            'previousCancelledReason' => $reason,
            'cart' => $cart,
            'order' => $order ? $this->presentOrder($order) : null,
            'timeline' => $this->buildTimeline($order),
            'savedContact' => $savedContact,
        ]);
    }

    public function createOrder(Request $request)
    {
        $sessionId = $request->session()->getId();
        $customer = [
            'user_id' => $request->user()->id, // Associate order with authenticated user
            'email' => $request->string('email')->toString() ?: 'guest@example.com',
            'name' => $request->string('name')->toString() ?: 'ゲスト',
            'address_line1' => $request->string('address_line1')->toString() ?: 'N/A',
        ];

        try {
            $order = $this->orders->createFromCart($sessionId, $customer)->loadMissing('items');
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'Cart is empty') {
                return response()->json([
                    'message' => 'カートが空です。チェックアウトに進む前に商品を追加してください。'
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'order' => $this->presentOrder($order),
            'timeline' => $this->buildTimeline($order),
            'redirect' => route('checkout.details', $order->order_number),
        ]);
    }

    public function details(Request $request, string $orderNumber)
    {
        $order = $this->findOrderForSession($orderNumber, $request)->loadMissing('items');

        $savedContact = null;
        if ($user = $request->user()) {
            $savedContact = [
                'email' => $user->email,
                'name' => $user->name,
            ];
        }

        return Inertia::render('Checkout/Wizard', [
            'step' => 'details',
            'previousCancelledReason' => null,
            'cart' => null,
            'order' => $this->presentOrder($order, true),
            'timeline' => $this->buildTimeline($order),
            'savedContact' => $savedContact,
        ]);
    }

    public function updateDetails(Request $request, string $orderNumber)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line1' => ['required', 'string', 'max:160'],
            'address_line2' => ['nullable', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:20'],
        ]);

        $order = $this->findOrderForSession($orderNumber, $request)->loadMissing('items');
        $order = $this->orders->updateCustomerDetails($order, $validated);

        return response()->json([
            'order' => $this->presentOrder($order, true),
            'timeline' => $this->buildTimeline($order),
            'redirect' => route('checkout.pay', ['orderNumber' => $order->order_number]),
        ]);
    }

    private function deriveLastAttemptReason(string $sessionId): ?string
    {
        $order = \App\Models\Order::where('cart_session_id', $sessionId)
            ->orderByDesc('id')->first();
        if (!$order) return null;
        if ($order->status === 'canceled') {
            return $order->cancel_reason ?: 'キャンセルされました';
        }
        $paymentId = \DB::table('payments')->where('order_id', $order->id)->orderByDesc('id')->value('id');
        if ($paymentId) {
            $status = \DB::table('payment_transactions')->where('payment_id', $paymentId)->orderByDesc('id')->value('status');
            if ($status === 'failed') return '支払い失敗';
        }
        return null;
    }

    /**
     * POST /checkout
     * Creates an Order from cart (if needed), then creates a Stripe Checkout Session and returns JSON { url, id, order_number }.
     */
    public function store(Request $request)
    {
        $sessionId = $request->session()->getId();
        $orderNumber = (string) $request->input('order_number', '');

        if ($orderNumber !== '') {
            $order = $this->findOrderForSession($orderNumber, $request)->loadMissing('items');
        } else {
            $customer = [
                'user_id' => $request->user()->id, // Associate order with authenticated user
                'email' => $request->string('email')->toString() ?: 'guest@example.com',
                'name'  => $request->string('name')->toString() ?: 'ゲスト',
                'address_line1' => $request->string('address_line1')->toString() ?: 'N/A',
            ];

            try {
                $order = $this->orders->createFromCart($sessionId, $customer)->loadMissing('items');
            } catch (\RuntimeException $e) {
                if ($e->getMessage() === 'Cart is empty') {
                    return response()->json([
                        'message' => 'カートが空です。チェックアウトに進む前に商品を追加してください。'
                    ], 422);
                }
                throw $e;
            }
        }

        // Create checkout session with Stripe (Embedded)
        $session = $this->stripe->createCheckoutSession($order, $sessionId);

        // Redirect client to our embedded pay page with client_secret
        $cs = $session->client_secret ?? null;
        return response()->json([
            'id' => $session->id,
            'client_secret' => $cs,
            'order_number' => $order->order_number,
            'redirect' => $cs ? route('checkout.pay', ['orderNumber' => $order->order_number, 'cs' => $cs]) : null,
        ]);
    }

    /**
     * GET /checkout/thanks/{orderNumber}
     * Displays final order summary. If a session_id is present, it will be used client-side for verification.
     */
    public function thanks(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items', 'payments'])
            ->firstOrFail();
        $timeline = \DB::table('order_status_history as h')
            ->join('order_statuses as s', 's.id', '=', 'h.to_status_id')
            ->where('h.order_id', $order->id)
            ->orderBy('h.changed_at')
            ->get(['s.code as status', 'h.changed_at'])
            ->map(fn($r) => [
                'status' => $r->status,
                'changed_at' => (string) $r->changed_at,
            ]);

        return Inertia::render('Checkout/Success', [
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_id' => $order->order_status_id,
                'subtotal_yen' => $order->subtotal_yen,
                'discount_yen' => $order->discount_yen,
                'coupon_code' => $order->coupon_code,
                'coupon_discount_yen' => $order->coupon_discount_yen,
                'shipping_yen' => $order->shipping_yen,
                'tax_yen' => $order->tax_yen,
                'total_yen' => $order->total_yen,
                'email' => $order->email,
                'confirmation_emailed_at' => optional($order->confirmation_emailed_at)->toIso8601String(),
                'cancellation_emailed_at' => optional($order->cancellation_emailed_at)->toIso8601String(),
                'payments' => $order->payments->map(fn($p) => [
                    'id' => $p->id,
                    'status_id' => $p->payment_status_id,
                    'processed_at' => optional($p->processed_at)->toIso8601String(),
                ]),
                'items' => $order->items->map(fn($i) => [
                    'name' => $i->name_snapshot,
                    'sku' => $i->sku_snapshot,
                    'qty' => $i->qty,
                    'unit_price_yen' => $i->unit_price_yen,
                    'line_total_yen' => $i->line_total_yen,
                ]),
                'timeline' => $timeline,
            ],
            'session_id' => $request->query('session_id'),
        ]);
    }

    /**
     * GET /checkout/success?session_id=...
     * Fetches the Stripe Checkout Session, resolves order_number via metadata, and shows the same success page.
     */
    public function success(Request $request)
    {
        $sessionId = (string) $request->query('session_id', '');
        if (!$sessionId) {
            abort(400, 'セッションIDは必須です');
        }

        $stripe = new \Stripe\StripeClient(config('stripe.secret'));
        $session = $stripe->checkout->sessions->retrieve($sessionId);
        $orderNumber = $session->metadata->order_number ?? null;
        if (!$orderNumber) {
            abort(404, '注文が見つかりません');
        }

        // Reuse the thanks() rendering by loading the order and building identical props
        $order = Order::where('order_number', $orderNumber)
            ->with(['items', 'payments'])
            ->firstOrFail();

        $timeline = \DB::table('order_status_history as h')
            ->join('order_statuses as s', 's.id', '=', 'h.to_status_id')
            ->where('h.order_id', $order->id)
            ->orderBy('h.changed_at')
            ->get(['s.code as status', 'h.changed_at'])
            ->map(fn($r) => [
                'status' => $r->status,
                'changed_at' => (string) $r->changed_at,
            ]);

        return Inertia::render('Checkout/Success', [
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_id' => $order->order_status_id,
                'subtotal_yen' => $order->subtotal_yen,
                'discount_yen' => $order->discount_yen,
                'coupon_code' => $order->coupon_code,
                'coupon_discount_yen' => $order->coupon_discount_yen,
                'shipping_yen' => $order->shipping_yen,
                'tax_yen' => $order->tax_yen,
                'total_yen' => $order->total_yen,
                'email' => $order->email,
                'confirmation_emailed_at' => optional($order->confirmation_emailed_at)->toIso8601String(),
                'cancellation_emailed_at' => optional($order->cancellation_emailed_at)->toIso8601String(),
                'payments' => $order->payments->map(fn($p) => [
                    'id' => $p->id,
                    'status_id' => $p->payment_status_id,
                    'processed_at' => optional($p->processed_at)->toIso8601String(),
                ]),
                'items' => $order->items->map(fn($i) => [
                    'name' => $i->name_snapshot,
                    'sku' => $i->sku_snapshot,
                    'qty' => $i->qty,
                    'unit_price_yen' => $i->unit_price_yen,
                    'line_total_yen' => $i->line_total_yen,
                ]),
                'timeline' => $timeline,
            ],
            'session_id' => $sessionId,
        ]);
    }

    /**
     * GET /checkout/cancel/{orderNumber}
     * Restores the order items back into the current cart and shows cancel page.
     */
    public function cancel(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->with(['items', 'payments'])->first();
        if ($order) {
            // Guard against race: if Stripe session is already complete/paid, do not cancel
            $sessionIdQ = (string) $request->query('session_id', '');
            $sessionId = $sessionIdQ !== ''
                ? $sessionIdQ
                : (string) optional($order->payments()->latest()->first())->payload_json['checkout_session_id'] ?? '';
            if ($sessionId !== '') {
                try {
                    $stripe = new \Stripe\StripeClient(config('stripe.secret'));
                    $cs = $stripe->checkout->sessions->retrieve($sessionId);
                    $status = (string) ($cs->status ?? '');
                    $pstatus = (string) ($cs->payment_status ?? '');
                    if ($status === 'complete' || $pstatus === 'paid') {
                        // Payment finished; send to thanks instead of cancel
                        return redirect()->route('checkout.thanks', ['orderNumber' => $order->order_number, 'session_id' => $sessionId]);
                    }
                } catch (\Throwable $e) {
                    // Ignore lookup errors; fall back to local checks
                }
            }
            $sessionId = $request->session()->getId();
            // Restore the cart to exactly what the order had (avoid doubling)
            $this->cart->clear($sessionId);
            foreach ($order->items as $item) {
                $this->cart->add($sessionId, (int)$item->product_variant_id, (int)$item->qty);
            }

            // Also mark order as cancelled (if not processed)
            $latestPay = $order->payments()->latest()->first();
            if (!$latestPay || !$latestPay->processed_at) {
                // Proactively expire the Stripe Checkout Session to prevent re-use of a dead session
                if (!empty($sessionIdQ)) {
                    try {
                        $stripe = new \Stripe\StripeClient(config('stripe.secret'));
                        $stripe->checkout->sessions->expire($sessionIdQ);
                    } catch (\Throwable $e) {
                    }
                }
                try {
                    $order->transitionTo('cancelled');
                } catch (\Throwable $e) {
                }
                $order->forceFill([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                    'pending_expires_at' => now(),
                    // If webhook didn't set a reason, treat as customer-driven cancel
                    'cancel_reason' => $order->cancel_reason ?: 'customer_canceled',
                ])->save();

                // Send canceled email once per order (DB flag)
                if (empty($order->cancellation_emailed_at)) {
                    try {
                        Mail::to($order->email)->queue(new OrderCanceledMail($order->fresh(['items'])));
                    } catch (\Throwable $e) {
                    }
                    $order->forceFill(['cancellation_emailed_at' => now()])->save();
                }
            }
        }

        return Inertia::render('Checkout/Cancel', [
            'order_number' => $orderNumber,
            'status' => $order?->status,
            'email' => $order?->email,
            'cancellation_emailed_at' => optional($order?->cancellation_emailed_at)->toIso8601String(),
            'cancel_reason' => $order?->cancel_reason,
        ]);
    }


    /**
     * GET /checkout/pay/{orderNumber}?cs=...
     * Renders the Embedded Checkout container with client_secret and publishable key.
     */
    public function pay(Request $request, string $orderNumber)
    {
        $order = $this->findOrderForSession($orderNumber, $request)->loadMissing('items', 'payments');
        $clientSecret = (string) $request->query('cs', '');
        if (!$clientSecret) abort(400, 'クライアントシークレットは必須です');
        $sid = (string) $request->query('sid', '');

        // If we have a session id, pre-check its status to avoid rendering a dead/finished checkout
        $allowCancel = true;
        $sessionStatus = 'unknown'; // open | expired | unknown
        if ($sid !== '') {
            try {
                $stripe = new \Stripe\StripeClient(config('stripe.secret'));
                $cs = $stripe->checkout->sessions->retrieve($sid);
                $status = (string) ($cs->status ?? '');
                $pstatus = (string) ($cs->payment_status ?? '');
                if ($status === 'complete' || $pstatus === 'paid') {
                    return redirect()->route('checkout.thanks', ['orderNumber' => $orderNumber, 'session_id' => $sid]);
                }
                if ($status === 'expired' || $status === 'canceled') {
                    // Render Pay with an expired status message and no embedded checkout
                    $allowCancel = false;
                    $sessionStatus = 'expired';
                } else {
                    $sessionStatus = 'open';
                }
                // Only allow cancel when session is still open and not paid
                $allowCancel = ($status === 'open') && ($pstatus !== 'paid');
            } catch (\Throwable $e) {
                // ignore lookup errors; render embedded and let it handle
            }
        }

        $response = Inertia::render('Checkout/Pay', [
            'order_number' => $orderNumber,
            'client_secret' => $clientSecret,
            'stripe_pk' => config('stripe.publishable_key'),
            'fallback_url' => (string) $request->query('hu', ''),
            'start_url' => route('checkout.index'),
            // Always provide a cancel URL; controller guards against post-payment
            'cancel_url' => $sid !== ''
                ? route('checkout.cancel', ['orderNumber' => $orderNumber, 'session_id' => $sid])
                : route('checkout.cancel', ['orderNumber' => $orderNumber]),
            'allow_cancel' => $allowCancel,
            'session_status' => $sessionStatus,
            'restart_url' => route('checkout.index'),
            'timeline' => $this->buildTimeline($order),
        ]);
        // Avoid BFCache/stale Pay page after completion; force revalidation
        $httpResponse = $response->toResponse($request);
        $httpResponse->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $httpResponse->headers->set('Pragma', 'no-cache');
        return $httpResponse;
    }

    private function presentOrder(Order $order, bool $withItems = false): array
    {
        // Get the current cart session ID to recalculate values if needed
        $currentCartSessionId = request()->session()->getId();

        // Check if this order is for the current session and if we should recalculate
        $shouldRecalculate = $order->cart_session_id === $currentCartSessionId;

        $subtotal_yen = $order->subtotal_yen;
        $discount_yen = $order->discount_yen;
        $coupon_discount_yen = $order->coupon_discount_yen;
        $tax_yen = $order->tax_yen;
        $total_yen = $order->total_yen;

        if ($shouldRecalculate) {
            // Get the current cart to ensure we're showing up-to-date values
            $userId = request()->user()?->id;
            $cart = $this->cart->get($currentCartSessionId, $userId);
            $subtotal_yen = (int) round(($cart['subtotal_cents'] ?? 0) / 100);
            $discount_yen = (int) round((int)($cart['savings_cents'] ?? 0) / 100);
            $coupon_discount_yen = (int) round((int)($cart['coupon_discount_cents'] ?? 0) / 100);
            $tax_yen = (int) round((int)($cart['tax_cents'] ?? 0) / 100);
            $total_yen = (int) round(($cart['total_cents'] ?? 0) / 100);
        }

        $payload = [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'subtotal_yen' => $subtotal_yen,
            'discount_yen' => $discount_yen,
            'coupon_code' => $order->coupon_code,
            'coupon_discount_yen' => $coupon_discount_yen,
            'shipping_yen' => $order->shipping_yen,
            'tax_yen' => $tax_yen,
            'total_yen' => $total_yen,
            'email' => $order->email,
            'name' => $order->name,
            'phone' => $order->phone,
            'address_line1' => $order->address_line1,
            'address_line2' => $order->address_line2,
            'city' => $order->city,
            'state' => $order->state,
            'zip' => $order->zip,
            'details_completed_at' => optional($order?->details_completed_at)->toIso8601String(),
            'payment_started_at' => optional($order?->payment_started_at)->toIso8601String(),
        ];

        if ($withItems) {
            $payload['items'] = $order->items->map(function ($item) {
                return [
                    'name' => $item->name_snapshot,
                    'sku' => $item->sku_snapshot,
                    'qty' => $item->qty,
                    'unit_price_yen' => $item->unit_price_yen,
                    'line_total_yen' => $item->line_total_yen,
                ];
            })->values();
        }

        return $payload;
    }

    private function buildTimeline(?Order $order): array
    {
        $orderStatus = $order?->status;
        $detailsCompleted = (bool) ($order?->details_completed_at);
        $paymentComplete = $order && in_array($orderStatus, ['processing', 'shipped', 'delivered', 'refunded']);
        $paymentCanceled = $orderStatus === 'canceled';

        $steps = [];

        $steps[] = [
            'key' => 'review',
            'label' => '確認',
            'status' => $order ? 'complete' : 'current',
        ];

        $steps[] = [
            'key' => 'details',
            'label' => '詳細',
            'status' => !$order
                ? 'pending'
                : ($detailsCompleted ? 'complete' : 'current'),
            'completed_at' => $detailsCompleted ? optional($order?->details_completed_at)->toIso8601String() : null,
        ];

        $paymentStatus = 'pending';
        if ($paymentCanceled) {
            $paymentStatus = 'canceled';
        } elseif ($paymentComplete) {
            $paymentStatus = 'complete';
        } elseif ($detailsCompleted) {
            $paymentStatus = 'current';
        }

        $steps[] = [
            'key' => 'payment',
            'label' => '支払い',
            'status' => $paymentStatus,
            'started_at' => optional($order?->payment_started_at)->toIso8601String(),
            'completed_at' => $paymentComplete ? optional($order?->updated_at)->toIso8601String() : null,
        ];

        return $steps;
    }

    private function findOrderForSession(string $orderNumber, Request $request): Order
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $sessionId = $request->session()->getId();
        $userId = $request->user()?->id;

        // For authenticated users, check if the order was created by them
        if ($userId && $order->user_id === $userId) {
            // If the order was created by the authenticated user, it's valid
            // regardless of session ID mismatch
            return $order;
        }

        // For guest orders, verify session matches
        if ($order->cart_session_id && $order->cart_session_id !== $sessionId) {
            \Log::warning('チェックアウトセッションの不一致', [
                'order_number' => $orderNumber,
                'stored_session' => $order->cart_session_id,
                'current_session' => $sessionId,
                'user_id' => $userId,
            ]);
            abort(403, 'この注文のチェックアウトセッションが一致しません。');
        }

        return $order;
    }
}
