<?php

return [
    'name' => 'Forge Framework',
    'cors' => [
        'allowed_origins' => env('CORS_ALLOWED_ORIGINS', ['*']),
        'allowed_methods' => env('CORS_ALLOWED_METHODS', ['GET, POST, PUT, DELETE, OPTIONS']),
        'allowed_headers' => env('CORS_ALLOWED_HEADERS', ['Content-Type', 'Authorization']),
    ],
    "log" => [
        'driver' => env("LOG_DRIVER", "syslog"),
        'path' => BASE_PATH .  "/storage/logs/forge.log"
    ],
];
