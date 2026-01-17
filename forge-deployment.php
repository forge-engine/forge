<?php

declare(strict_types=1);

return [
    'php_executable' => '/Users/acidlake/Library/Application Support/Herd/bin/php',
    'server' => [
        'name' => 'my-app-server',
        'region' => 'nyc1',
        'size' => 's-1vcpu-1gb',
        'image' => 'ubuntu-22-04-x64',
        'ssh_key_path' => null,
    ],
    'provision' => [
        'php_version' => '8.4',
        'database_type' => 'sqlite',
        'database_version' => '8',
        'database_name' => 'forge_app',
        'database_user' => 'forge_user',
        'database_password' => 'secret',
    ],
    'deployment' => [
        'domain' => 'forgev9.upper.do',
        'ssl_email' => 'jeremias2@gmail.com',
        'commands' => [],
        'post_deployment_commands' => [
            'cache:flush',
            'cache:warm',
            'db:migrate --type=all',
            'storage:link',
            'modules:forge-deployment:fix-permissions',
            'modules:forgewire:minify',
            'asset:link --type=module --module=forge-wire',
        ],
        'env_vars' => [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'true',
            'CENTRAL_DOMAIN' => 'forgev9.upper.do',
            'CORS_ALLOWED_ORIGINS' => [
                'https://forgev9.upper.do',
            ],
            'IP_WHITE_LIST' => [
                '127.0.0.1',
                '::1',
                '165.22.14.25',
            ],
        ],
    ],
];