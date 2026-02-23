<?php

return [
    'context' => [
        'max_products' => env('AI_CONTEXT_MAX_PRODUCTS', 10),
        'max_trending' => env('AI_CONTEXT_MAX_TRENDING', 3),
        'max_top_rated' => env('AI_CONTEXT_MAX_TOP_RATED', 3),
    ],

    'ollama' => [
        'host' => env('OLLAMA_HOST', 'http://host.docker.internal:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen3:8b'),
        'fallback_models' => explode(',', env('OLLAMA_FALLBACK_MODELS', 'qwen3-vl:8b')),
        'timeout' => env('OLLAMA_TIMEOUT', 60),
        'keep_alive' => env('OLLAMA_KEEP_ALIVE', -1),
        'options' => [
            'temperature' => env('OLLAMA_TEMPERATURE', 0.3),
            'num_ctx' => env('OLLAMA_NUM_CTX', 2048),
            'num_batch' => env('OLLAMA_NUM_BATCH', 2048),
            'num_predict' => env('OLLAMA_NUM_PREDICT', 150),
            'num_parallel' => env('OLLAMA_NUM_PARALLEL', 4),
            'top_p' => env('OLLAMA_TOP_P', 0.9),
        ],
    ],
];
