<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager;

use App\Modules\ForgePackageManager\Contracts\PackageManagerInterface;
use App\Modules\ForgePackageManager\Services\PackageManagerService;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;

#[Module(
  name: 'ForgePackageManager',
  version: '3.3.0',
  description: 'A Package Manager By Forge',
  order: 1,
  isCli: true,
  author: 'Forge Team',
  license: 'MIT',
  type: 'management',
  tags: ['management', 'package', 'dependency', 'installer']
)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[ConfigDefaults(defaults: [
  'source_list' => [
  ]
])]
final class ForgePackageManager
{
  public function register(Container $container): void
  {
    if (PHP_SAPI === 'cli') {
      $container->bind(PackageManagerInterface::class, PackageManagerService::class);
    }
  }

  public function setupConfigDefaults(Container $container): void
  {
    $forgeSourceList = [
      'registry' => [
        [
          'name' => 'forge-engine-modules',
          'type' => 'git',
          'url' => 'https://github.com/forge-engine/modules',
          'branch' => 'main',
          'private' => false,
          'personal_token' => env('GITHUB_TOKEN', ''),
          'description' => 'Forge Kernel Official Modules'
        ]
      ],
      'cache_ttl' => env('SOURCE_LIST_CACHE_TTL', 3600)
    ];

    /** @var Config $config */
    $config = $container->get(Config::class);
    $config->set('forge_package_manager.source_list', $forgeSourceList);
  }
}
