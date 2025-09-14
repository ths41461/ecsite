<?php

return [
    'secret' => env('STRIPE_SECRET', ''),
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
];

