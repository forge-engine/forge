<?php

return [
    'name' => 'Forge Framework',
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET, POST, PUT, DELETE, OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization'],
    ],
    "log" => [
        'driver' =>  $_ENV["LOG_DRIVER"] ?? "syslog",
        'path' => BASE_PATH .  "/storage/logs/forge.log"
    ],
];
