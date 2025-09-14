<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order {{ $order->order_number }} canceled</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; color: #111; }
        .wrap { max-width: 640px; margin: 0 auto; padding: 16px; }
        .muted { color: #6b7280; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
    </style>
    @php
        $yen = fn($v) => number_format($v) . '円';
    @endphp
</head>
<body>
<div class="wrap">
    <h1>Your payment attempt was canceled.</h1>
    <p class="muted">Order #{{ $order->order_number }}</p>
    <p>
        Hi {{ $order->name }},<br>
        We’ve canceled this order. If this was a mistake, you can restart checkout anytime.
    </p>

    <table role="presentation" aria-label="Order summary">
        <tbody>
        <tr><th>Subtotal</th><td>{{ $yen($order->subtotal_yen) }}</td></tr>
        @if($order->discount_yen > 0)
            <tr><th>Discount</th><td>-{{ $yen($order->discount_yen) }}</td></tr>
        @endif
        <tr><th><strong>Total</strong></th><td><strong>{{ $yen($order->total_yen) }}</strong></td></tr>
        </tbody>
    </table>

    <h3 style="margin-top:16px;">Items</h3>
    <table role="presentation" aria-label="Items">
        <thead>
            <tr><th>Item</th><th>Qty</th><th align="right">Line total</th></tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->name_snapshot }} <span class="muted">({{ $item->sku_snapshot }})</span></td>
                <td>{{ $item->qty }}</td>
                <td align="right">{{ $yen($item->line_total_yen) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p class="muted" style="margin-top: 16px;">This message was sent to {{ $order->email }}.</p>
</div>
</body>
</html>

