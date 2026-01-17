<?php

declare(strict_types=1);

return [
    'server' => [
        'name' => 'my-app-server',
        'region' => 'nyc1',
        'size' => 's-1vcpu-1gb',
        'image' => 'ubuntu-22-04-x64',
        'ssh_key_path' => null,
    ],

    'provision' => [
        'php_version' => '8.4',
        'database_type' => 'mysql',
        'database_version' => '8.0',
        'database_name' => 'forge_app',
        'database_user' => 'forge_user',
        'database_password' => 'secret',
    ],

    'deployment' => [
        'domain' => 'example.com',
        'ssl_email' => 'admin@example.com',
        'commands' => [
        ],
        'post_deployment_commands' => [
            'cache:flush',
            'migrate',
        ],
        'env_vars' => [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
        ],
    ],
];