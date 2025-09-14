<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    /**
     * GET /orders/{orderNumber}
     * Returns JSON summary for polling the Success page.
     */
    public function show(Request $request, string $orderNumber)
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

        return response()->json([
            'order_number' => $order->order_number,
            'status_id' => $order->order_status_id,
            'subtotal_yen' => $order->subtotal_yen,
            'discount_yen' => $order->discount_yen,
            'shipping_yen' => $order->shipping_yen,
            'tax_yen' => $order->tax_yen,
            'total_yen' => $order->total_yen,
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
        ]);
    }

    /**
     * GET /orders/{orderNumber}/view
     * HTML view of order using Inertia + OrderSummary.
     */
    public function view(Request $request, string $orderNumber)
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

        return \Inertia\Inertia::render('Orders/Show', [
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
                'items' => $order->items->map(fn($i) => [
                    'name' => $i->name_snapshot,
                    'sku' => $i->sku_snapshot,
                    'qty' => $i->qty,
                    'unit_price_yen' => $i->unit_price_yen,
                    'line_total_yen' => $i->line_total_yen,
                ]),
                'payments' => $order->payments->map(fn($p) => [
                    'id' => $p->id,
                    'status_id' => $p->payment_status_id,
                    'processed_at' => optional($p->processed_at)->toIso8601String(),
                ]),
                'timeline' => $timeline,
                'confirmation_emailed_at' => optional($order->confirmation_emailed_at)->toIso8601String(),
                'cancellation_emailed_at' => optional($order->cancellation_emailed_at)->toIso8601String(),
            ],
        ]);
    }
}
