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
        return Inertia::render('Checkout/Start', [
            'previousCancelledReason' => $reason, // 'timeout' | 'changed' | 'expired' | 'psp_canceled' | 'failed' | null
            'pendingOrderNumber' => $reusable?->order_number,
        ]);
    }

    private function deriveLastAttemptReason(string $sessionId): ?string
    {
        $order = \App\Models\Order::where('cart_session_id', $sessionId)
            ->orderByDesc('id')->first();
        if (!$order) return null;
        if ($order->status === 'canceled') {
            return $order->cancel_reason ?: 'canceled';
        }
        $paymentId = \DB::table('payments')->where('order_id', $order->id)->orderByDesc('id')->value('id');
        if ($paymentId) {
            $status = \DB::table('payment_transactions')->where('payment_id', $paymentId)->orderByDesc('id')->value('status');
            if ($status === 'failed') return 'failed';
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

        // Minimal customer snapshot for now; extend with real form data later
        $customer = [
            'email' => $request->string('email')->toString() ?: 'guest@example.com',
            'name'  => $request->string('name')->toString() ?: 'Guest',
            'address_line1' => $request->string('address_line1')->toString() ?: 'N/A',
        ];

        // If an order already exists for this cart in session, reuse; otherwise create
        $order = $this->orders->createFromCart($sessionId, $customer);

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
            abort(400, 'session_id is required');
        }

        $stripe = new \Stripe\StripeClient(config('stripe.secret'));
        $session = $stripe->checkout->sessions->retrieve($sessionId);
        $orderNumber = $session->metadata->order_number ?? null;
        if (!$orderNumber) {
            abort(404, 'Order not found');
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
        $clientSecret = (string) $request->query('cs', '');
        if (!$clientSecret) abort(400, 'client_secret is required');
        $sid = (string) $request->query('sid', '');

        return Inertia::render('Checkout/Pay', [
            'order_number' => $orderNumber,
            'client_secret' => $clientSecret,
            'stripe_pk' => config('stripe.publishable_key'),
            'fallback_url' => (string) $request->query('hu', ''),
            'start_url' => route('checkout.index'),
            // Always provide a cancel URL; controller guards against post-payment
            'cancel_url' => $sid !== ''
                ? route('checkout.cancel', ['orderNumber' => $orderNumber, 'session_id' => $sid])
                : route('checkout.cancel', ['orderNumber' => $orderNumber]),
        ]);
    }
}
