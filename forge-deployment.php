<?php

declare(strict_types=1);

return [
  'server' => [
    'name' => 'forge-v3-app-server',
    'region' => 'nyc1',
    'size' => 's-1vcpu-1gb',
    'image' => 'ubuntu-22-04-x64',
    'ssh_key_path' => null,
  ],

  'provision' => [
    'php_version' => '8.4',
    'database_type' => 'mysql',
    'database_version' => '8.0',
  ],

  'deployment' => [
    'domain' => 'forge-v3.upper.do',
    'ssl_email' => 'jeremias2@gmail.com',
    'commands' => [
    ],
    'post_deployment_commands' => [
      'cache:flush',
      'cache:warm',
      'db:migrate --type=all'
    ],
    'env_vars' => [
      'APP_ENV' => 'production',
      'APP_DEBUG' => 'false',
    ],
  ],
];
