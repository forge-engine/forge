<?php

return [
    'api_keys' => ['your-secure-api-key'],
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
        'secret' => 'your-very-secure-secret-key',
    ],
    'password' => [
        'password_cost' => 12,
        'max_login_attempts' => 3,
        'lockout_time' => 300
    ]
];
