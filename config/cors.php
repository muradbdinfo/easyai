<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'https://ai.murad.bd',
        'https://aiadmin.murad.bd',
    ],
    'allowed_origins_patterns' => [
        '#^https://.*\.murad\.bd$#',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
