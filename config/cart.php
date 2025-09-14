<?php

return [
    // How long the cart survives in Redis (seconds)
    'ttl_seconds' => env('CART_TTL_SECONDS', 14 * 24 * 60 * 60),

    // Default currency code for computed totals (format on the client)
    'currency' => env('CART_CURRENCY', 'JPY'),

    // Max quantity per line
    'max_qty' => env('CART_MAX_QTY', 20),

    // Optionally clear the cart once payment succeeds (via Stripe webhook)
    'clear_on_payment_success' => env('CART_CLEAR_ON_PAYMENT_SUCCESS', true),

    // Reuse protection: how long a pending order is considered valid (minutes)
    'order_pending_ttl_minutes' => env('ORDER_PENDING_TTL_MINUTES', 60),
];
