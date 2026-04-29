<?php

return [
    'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
    'model' => env('OLLAMA_MODEL', 'gemma4:e2b'),
    'context_limit' => (int) env('OLLAMA_CONTEXT_LIMIT', 6000),
    'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
    'available_models' => array_filter(array_map('trim', explode(',', env('OLLAMA_AVAILABLE_MODELS', 'gemma4:e2b')))),
];
