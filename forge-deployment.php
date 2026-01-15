<?php

declare(strict_types=1);

return [
  'server' => [
    'name' => 'forge-v4-app-server',
    'region' => 'nyc1',
    'size' => 's-1vcpu-1gb',
    'image' => 'ubuntu-22-04-x64',
    'ssh_key_path' => null,
  ],

  'provision' => [
    'php_version' => '8.4',
    'database_type' => 'sqlite',
    'database_version' => '8.0',
    'database_name' => 'forge_v3',
    'database_user' => 'forge_user',
    'database_password' => 'forge_password',
  ],

  'deployment' => [
    'domain' => 'forgev6.upper.do',
    'ssl_email' => 'jeremias2@gmail.com',
    'commands' => [
    ],
    'post_deployment_commands' => [
      'cache:flush',
      'cache:warm',
      'db:migrate --type=all',
      'modules:forge-deployment:fix-permissions',
      'storage:link'
    ],
    'env_vars' => [
      'APP_ENV' => 'production',
      'APP_DEBUG' => 'false',
    ],
  ],
];
