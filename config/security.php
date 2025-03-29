<?php

return [
    'api_keys' => ['your-secure-api-key'],
    'ip_whitelist' => ['127.0.0.1', '192.168.1.216', "::1"],
    'rate_limit' => [
        'max_requests' => 100,
        'time_window' => 60,
    ],
    'circuit_breaker' => [
        'max_failures' => 1,
        'reset_time' => 1,
    ],
    'jwt' => [
        'secret' => 'your-very-secure-secret-key',
    ],
    'password' => [
        'password_cost' => 12,
        'max_login_attempts' => 3,
        'lockout_time' => 300 // 5 minutes
    ]
];
