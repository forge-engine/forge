<?php

return [
    'version' => '3.4.1',
    'source_url' => 'https://raw.githubusercontent.com/JTorresConsulta/TailwindCSS-offline/refs/heads/main/all-tailwind-classes-full.css',
    'custom_css' => BASE_PATH . '/app/resources/assets/css/custom.css',
    'auto_download' => true,
    'offline_fallback' => true,
    'verify_integrity' => true,

    'input_css' => BASE_PATH . '/app/resources/assets/css/tailwind.css',
    'output_css' => BASE_PATH . '/public/assets/css/forgetailwind.css',

    'content' => [
        BASE_PATH . '/app/resources/views/**/*.php',
        BASE_PATH . '/app/resources/assets/**/*.js',
        BASE_PATH . '/app/resources/assets/**/*.css',
        BASE_PATH . '/modules/**/src/resources/views/**/*.php',
        BASE_PATH . '/modules/**/src/resources/assets/**/*.js',
        BASE_PATH . '/modules/**/src/resources/assets/**/*.css',
    ],

    'theme' => [
        'extend' => [
            'fontFamily' => [
                'sans' => ['Inter', 'ui-sans-serif', 'system-ui'],
            ],
            'colors' => [
                'primary' => '#1d4ed8',
                'secondary' => '#9333ea',
            ],
            'spacing' => [
                '72' => '18rem',
                '84' => '21rem',
            ],
        ],
    ],

    'plugins' => [
    ],

    'tokens' => [
        // 'primary' => '#1d4ed8',
        // 'secondary' => '#9333ea',
        // 'radius' => '0.5rem',
        // 'spacing-xs' => '0.25rem',
        // 'spacing-unit' => '1rem',
    ],
];
