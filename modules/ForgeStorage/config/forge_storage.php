<?php
return [
    'default_driver' => 'local',
    'root_path' => 'storage/app',
    'public_path' => 'public/storage',
    'buckets' => [
        'uploads' => [
            'driver' => 'local',
            'public' => false,
            'expire' => 3600 // 1 hour
        ]
    ]
];