<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>注文 {{ $order->order_number }} がキャンセルされました</title>
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
    <h1>お支払いがキャンセルされました。</h1>
    <p class="muted">注文番号 #{{ $order->order_number }}</p>
    <p>
        {{ $order->name }} 様<br>
        ご注文をキャンセルしました。もし誤ってキャンセルされた場合は、いつでもチェックアウトを再開できます。
    </p>

    <table role="presentation" aria-label="注文概要">
        <tbody>
        <tr><th>小計</th><td>{{ $yen($order->subtotal_yen) }}</td></tr>
        @if($order->discount_yen > 0)
            <tr><th>割引</th><td>-{{ $yen($order->discount_yen) }}</td></tr>
        @endif
        <tr><th><strong>合計</strong></th><td><strong>{{ $yen($order->total_yen) }}</strong></td></tr>
        </tbody>
    </table>

    <h3 style="margin-top:16px;">商品</h3>
    <table role="presentation" aria-label="商品">
        <thead>
            <tr><th>商品</th><th>数量</th><th align="right">小計</th></tr>
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

    <p class="muted" style="margin-top: 16px">このメッセージは {{ $order->email }} に送信されました。</p>
</div>
</body>
</html>