<?php

return [
    'context' => [
        'max_products' => env('AI_CONTEXT_MAX_PRODUCTS', 10),
        'max_trending' => env('AI_CONTEXT_MAX_TRENDING', 3),
        'max_top_rated' => env('AI_CONTEXT_MAX_TOP_RATED', 3),
    ],

    'ollama' => [
        'host' => env('OLLAMA_HOST', 'http://ollama:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen3:8b'),
        'fallback_models' => explode(',', env('OLLAMA_FALLBACK_MODELS', 'llama3.1:8b')),
        'timeout' => env('OLLAMA_TIMEOUT', 300),
        'keep_alive' => env('OLLAMA_KEEP_ALIVE', -1),
        'options' => [
            'temperature' => (float) env('OLLAMA_TEMPERATURE', 0.1),
            'num_ctx' => (int) env('OLLAMA_NUM_CTX', 2048),
            'num_predict' => (int) env('OLLAMA_NUM_PREDICT', 256),
            'top_p' => (float) env('OLLAMA_TOP_P', 0.9),
            'num_thread' => (int) env('OLLAMA_NUM_THREAD', 4),
        ],
    ],

    'cache_ttl_seconds' => env('AI_CACHE_TTL_SECONDS', 3600 * 24),
];
