<?php

declare(strict_types=1);

return [
    'server' => [
        'name' => 'forge-v4-app-server',
        'region' => 'nyc1',
        'size' => 's-1vcpu-1gb',
        'image' => 'ubuntu-22-04-x64',
    ],
    'provision' => [
        'php_version' => 8.4,
        'database_type' => 'sqlite',
        'database_version' => 8,
        'database_name' => 'forge_v3',
    ],
    'deployment' => [
        'domain' => 'forgev8.upper.do',
        'ssl_email' => 'jeremias2@gmail.com',
    ],
];