<?php

return [
    'app_key' => env('APP_KEY', 'your-secure-app-key'),
    'api_keys' => env('API_KEYS', ['your-secure-api-key']),
    'ip_whitelist' => env('IP_WHITE_LIST', []),
    'rate_limit' => [
        'max_requests' => env('RATE_LIMIT_MAX_REQUESTS', 40),
        'time_window' => env('RATE_LIMIT_TIME_WINDOW', 60),
    ],
    'circuit_breaker' => [
        'max_failures' => env('CIRCUIT_BREAKER_MAX_FAILURES', 5),
        'reset_time' => env('CIRCUIT_BREAKER_RESET_TIME', 300),
    ],
    'jwt' => [
        'enabled' => env('JWT_ENABLED', false),
        'secret' => env('JWT_SECRET', 'your-secure-jwt-secret'),
        'ttl' => env('JWT_TTL', 900),
        'refresh_ttl' => env('JWT_REFRESH_TTL', 604800),
    ],
    'password' => [
        'password_cost' => env('PASSWORD_COST', 12),
        'max_login_attempts' => env('MAX_LOGIN_ATTEMPTS', 3),
        'lockout_time' => env('LOCKOUT_TIME', 300),
        'min_password_length' => env('MIN_PASSWORD_LENGTH', 6),
        'max_password_length' => env('MAX_PASSWORD_LENGTH', 256)
    ],
    'auth' => [
        'redirect' => [
            'after_login' => '/dashboard',
            'after_logout' => '/',
        ],
    ],
];
