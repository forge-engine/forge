<?php

declare(strict_types=1);

return [
  'provider' => env('STORAGE_PROVIDER', 'local'),
  'root_path' => env('STORAGE_ROOT_PATH', 'storage/app'),
  'public_path' => env('STORAGE_PUBLIC_PATH', 'public/storage'),

  'drivers' => [
    'local' => [
      // Local driver uses root_path and public_path from top level
    ],
    's3' => [
      'key' => env('AWS_ACCESS_KEY_ID'),
      'secret' => env('AWS_SECRET_ACCESS_KEY'),
      'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
      'bucket' => env('AWS_BUCKET'),
      'endpoint' => env('AWS_ENDPOINT'), // For S3-compatible services
      // Note: S3Driver is currently a stub. Full implementation requires aws/aws-sdk-php
    ],
  ],

  'signed_url' => [
    'default_expiration' => 3600, // 1 hour
    'max_expiration' => 86400, // 24 hours
  ],

  'hash_filenames' => env('STORAGE_HASH_FILENAMES', true),
];
