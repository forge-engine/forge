<?php

return [
    'registry' => [
        [
            'name' => 'forge-engine-modules',
            'type' => 'git',
            'url' => 'https://github.com/forge-engine/modules',
            'branch' => 'main',
            'private' => false,
            'personal_token' => env('GITHUB_TOKEN')
        ]
    ],
    'cache_ttl' => 3600
];

