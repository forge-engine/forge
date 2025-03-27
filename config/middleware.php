<?php

return [
    'global' => [
        \Forge\Core\Http\Middlewares\CorsMiddleware::class,
        \Forge\Core\Http\Middlewares\CompressionMiddleware::class,
    ],
    'web' => [
        \Forge\Core\Http\Middlewares\SessionMiddleware::class,
        \Forge\Core\Http\Middlewares\CookieMiddleware::class,
    ],
    'api' => [
        //\Forge\Core\Http\Middlewares\ApiAuthMiddleware::class,
        //\Forge\Core\Http\Middlewares\RateLimitMiddleware::class,
        \Forge\Core\Http\Middlewares\ApiMiddleware::class,
    ]
];
