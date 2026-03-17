<?php

return [
    'url'     => env('OPENCLAW_URL', 'http://127.0.0.1:18789'),
    'token'   => env('OPENCLAW_GATEWAY_TOKEN', ''),
    'model'   => env('OPENCLAW_MODEL', 'ollama/llama3.2'),
    'timeout' => (int) env('OPENCLAW_TIMEOUT', 120),
    'enabled' => env('OPENCLAW_ENABLED', true),
];