<?php

declare(strict_types=1);

return [
  'digitalocean' => [
    'api_token' => env('DIGITALOCEAN_API_TOKEN', ''),
  ],
  'cloudflare' => [
    'api_token' => env('CLOUDFLARE_API_TOKEN', ''),
  ],
];
