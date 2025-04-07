<?php

return [
    'global' => [
        \Forge\Core\Http\Middlewares\RateLimitMiddleware::class,
        \Forge\Core\Http\Middlewares\CircuitBreakerMiddleware::class,
        \Forge\Core\Http\Middlewares\CorsMiddleware::class,
        \Forge\Core\Http\Middlewares\SanitizeInputMiddleware::class,
        \Forge\Core\Http\Middlewares\CompressionMiddleware::class,
    ],
    'web' => [
        //\Forge\Core\Http\Middlewares\RelaxSecurityHeadersMiddleware::class,
        \Forge\Core\Http\Middlewares\SessionMiddleware::class,
        \Forge\Core\Http\Middlewares\CookieMiddleware::class,
    ],
    'api' => [
        \Forge\Core\Http\Middlewares\IpWhiteListMiddleware::class,
        \Forge\Core\Http\Middlewares\ApiKeyMiddleware::class,
        \Forge\Core\Http\Middlewares\ApiMiddleware::class,
    ]
];
