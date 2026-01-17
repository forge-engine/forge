<?php

return [
  'registry' => [
    [
      'name' => 'forge-engine-modules',
      'type' => 'git',
      'url' => 'https://github.com/forge-engine/modules',
      'branch' => 'main',
      'private' => false,
      'personal_token' => env('GITHUB_TOKEN'),
      'description' => 'Forge Kernel Official Modules'
    ]
  ],
  'cache_ttl' => 3600
];

