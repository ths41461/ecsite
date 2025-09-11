<?php

return [
    // How long the cart survives in Redis (seconds)
    'ttl_seconds' => env('CART_TTL_SECONDS', 14 * 24 * 60 * 60),

    // Default currency code for computed totals (format on the client)
    'currency' => env('CART_CURRENCY', 'JPY'),

    // Max quantity per line
    'max_qty' => env('CART_MAX_QTY', 20),
];
