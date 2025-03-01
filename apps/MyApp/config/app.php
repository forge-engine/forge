<?php
return [
    'name' => 'MyApp',
    "key" => \Forge\Core\Helpers\App::env("FORGE_APP_KEY"),
    'middleware' => [
        //\Forge\Http\Middleware\ErrorHandlingMiddleware::class,
        \Forge\Http\Middleware\SecurityHeadersMiddleware::class,
        \Forge\Http\Middleware\CorsMiddleware::class,
        \Forge\Http\Middleware\CompressionMiddleware::class,
        \MyApp\Middleware\StartSessionMiddleware::class,
    ],
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
        'allowed_headers' => ['Content-Type', 'Authorization'],
    ],
    "paths" => [
        "resources" => [
            "views" => "apps/MyApp/resources/views",
            "components" => "apps/MyApp/resources/components",
            "layouts" => "apps/MyApp/resources/layouts",
            "spark" => "apps/MyApp/resources/spark"
        ],
        "public" => [
            "assets" => "public/assets",
            "modules" => "public/modules",
            "uploads" => "public/uploads"
        ],
        "database" => [
            "migrations" => "apps/MyApp/Database/Migrations",
            "seeders" => "apps/MyApp/Database/Seeders"
        ],
        "controllers" => "apps/MyApp/Controllers",
        "models" => "apps/MyApp/Models",
        "routes" => "app/MyApp/routes",
        "events" => "apps/MyApp/Events",
        "helpers" => "apps/MyApp/Helpers",
        "middlewares" => "apps/MyApp/Middleware",
        "commands" => "apps/MyApp/Commands",
        "config" => "apps/MyApp/config",
        "interfaces" => "apps/MyApp/Contracts",
        "services" => "apps/MyApp/Services",
        "traits" => "apps/MyApp/Traits",
    ],
];
