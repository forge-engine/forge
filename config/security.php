<?php

return [
  'app_key' => env('APP_KEY', 'your-secure-app-key'),
  'api_keys' => env('API_KEYS', ['your-secure-api-key']),
  'ip_whitelist' => env('IP_WHITE_LIST', []),
  'rate_limit' => [
    'enabled' => env('RATE_LIMIT_ENABLED', true),
    'max_requests' => env('RATE_LIMIT_MAX_REQUESTS', 40),
    'time_window' => env('RATE_LIMIT_TIME_WINDOW', 60),
    'disable_in_dev' => env('RATE_LIMIT_DISABLE_IN_DEV', true),
    'bypass_ips' => env('RATE_LIMIT_BYPASS_IPS', ['127.0.0.1', '::1', 'localhost']),
  ],
  'circuit_breaker' => [
    'max_failures' => env('CIRCUIT_BREAKER_MAX_FAILURES', 5),
    'reset_time' => env('CIRCUIT_BREAKER_RESET_TIME', 300),
    'disable_in_dev' => env('CIRCUIT_BREAKER_DISABLE_IN_DEV', true),
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
      'after_login' => '/',
      'after_logout' => '/',
    ],
  ],
  'csp' => [
    'enabled' => env('CSP_ENABLED', true),
    'directives' => [
      'default-src' => ["'self'"],
      'script-src' => ["'self'", "'unsafe-inline'"],
      'style-src' => ["'self'", "'unsafe-inline'"],
    ],
    'external_assets' => [
      ...external_asset_config(
        name: 'font_awesome',
        url: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        integrity: 'sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==',
        crossorigin: 'anonymous'
      ),
    ],
  ],
];
