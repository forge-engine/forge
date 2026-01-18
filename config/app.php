<?php

return [
  'name' => 'Forge Kernel',
  'cors' => [
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', ['*']),
    'allowed_methods' => env('CORS_ALLOWED_METHODS', ['GET, POST, PUT, DELETE, OPTIONS']),
    'allowed_headers' => env('CORS_ALLOWED_HEADERS', ['Content-Type', 'Authorization']),
  ],
  'env' => env('APP_ENV', 'development'),
  'disabled_modules' => env('DISABLED_MODULES', []),
  'metrics' => [
    'enabled' => env('APP_METRICS_ENABLED', false),
  ],
];
