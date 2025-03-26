<?php

return [
    'global' => [
        \Forge\Core\Http\Middlewares\SessionMiddleware::class,
        \Forge\Core\Http\Middlewares\CookieMiddleware::class,
        \Forge\Core\Http\Middlewares\CorsMiddleware::class,
        \Forge\Core\Http\Middlewares\CompressionMiddleware::class,
    ]
];
