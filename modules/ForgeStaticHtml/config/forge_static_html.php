<?php
return [
    'output_dir' => 'public/static',
    'base_url' => '/',
    'clean_build' => true,
    'copy_assets' => true,
    'asset_dirs' => [
        'public/assets',
        'public/images'
    ],
    'include_paths' => [
        '/docs',
    ],
    'dynamic_routes' => [
        'documentation' => [
            'route_pattern' => '/docs/{category}/{slug}',
            'data_source' => 'database',
            'options' => [
                'categories_table' => 'categories',
                'sections_table' => 'sections',
                'category_slug_column' => 'slug',
                'section_slug_column' => 'slug',
                'section_category_id_column' => 'category_id',
                'batch_size' => 100,
            ],
        ],
    ],
];