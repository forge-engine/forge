<?php

return [
    'global' => [
        // Load your middlewares here
        //\Namespace\MiddlewareName::class
    ],
    'web' => [
        \App\Modules\ForgeWire\Middlewares\ForgeWireMiddleware::class,
    ],
    'api' => [
        // Load your middlewares here
        //\Namespace\MiddlewareName::class
    ],
    'api-auth' => [
        App\Modules\ForgeAuth\Middlewares\ApiJwtMiddleware::class,
    ]
];
