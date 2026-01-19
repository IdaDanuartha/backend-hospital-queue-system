<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => env('APP_ENV') === 'local' 
        ? ['*']
        : [
            'http://localhost:3000',
            'http://localhost:5173',
            'http://127.0.0.1:3000',
            'https://hospital-queue-api.codewithdanu.my.id',
        ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];