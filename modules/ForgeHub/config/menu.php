<?php

return [
    [
        'label' => 'CLI Command',
        'route' => '/hub/commands',
        'icon' => 'cog',
        'order' => 1,
        'permissions' => [
            'run:command',
            'view:command',
        ],
    ],
    [
        'label' => 'Logs',
        'route' => '/hub/logs',
        'icon' => 'log',
        'order' => 99,
        'permissions' => [
        ],
    ],
];
