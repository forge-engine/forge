<?php

use Forge\Core\Helpers\Path;
use Forge\Core\Helpers\App;

return [
    'default' => App::env('FORGE_DB_CONNECTION', 'mysql'),
    'connections' => [
        'memory' => [
            'driver' => 'sqlite',
            'database' => Path::storagePath('memory.sqlite'),
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => Path::storagePath('database.sqlite'),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => App::env('FORGE_DB_HOST', '127.0.0.1'),
            'port' => App::env('FORGE_DB_PORT', '3306'),
            'database' => App::env('FORGE_DB_DATABASE', 'forge'),
            'username' => App::env('FORGE_DB_USERNAME', 'root'),
            'password' => App::env('FORGE_DB_PASSWORD', 'root'),
            'charset' => 'utf8mb4',
            'ssl' => [
                'ca' => App::env('FORGE_DB_MYSQL_SSL_CA', null),
                'cert' => App::env('FORGE_DB_MYSQL_SSL_CERT', null),
                'key' => App::env('FORGE_DB_MYSQL_SSL_KEY', null),
            ],
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => App::env('FORGE_DB_PGSQL_HOST', '127.0.0.1'),
            'port' => App::env('FORGE_DB_PGSQL_PORT', '5432'),
            'database' => App::env('FORGE_DB_PGSQL_DATABASE', 'forge'),
            'username' => App::env('FORGE_DB_PGSQL_USERNAME', 'postgres'),
            'password' => App::env('FORGE_DB_PGSQL_PASSWORD', 'postgres'),
            'charset' => App::env('FORGE_DB_PGSQL_CHARSET', 'utf8'),
            'sslmode' => App::env('FORGE_DB_PGSQL_SSLMODE', 'disable'), // Default SSL mode, adjust as needed ('disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full')
            'ssl' => [
                'ca' => App::env('FORGE_DB_PGSQL_SSL_CA'),
                'cert' => App::env('FORGE_DB_PGSQL_SSL_CERT'),
                'key' => App::env('FORGE_DB_PGSQL_SSL_KEY'),
                'verify_peer' => App::env('FORGE_DB_PGSQL_SSL_VERIFY_PEER', false),
                'verify_peer_name' => App::env('FORGE_DB_PGSQL_SSL_VERIFY_PEER_NAME', false),
            ],
        ],
        'redis' => [
            'driver' => 'redis',
            'host' => App::env('FORGE_DB_REDIS_HOST', '127.0.0.1'),
            'port' => App::env('FORGE_DB_REDIS_PORT', 6379),
            //'auth' => App::env('FORGE_DB_REDIS_PASSWORD'),
            'database' => App::env('FORGE_DB_REDIS_DATABASE', 0),
            'timeout' => App::env('FORGE_DB_REDIS_TIMEOUT', 30)
        ],
    ]
];