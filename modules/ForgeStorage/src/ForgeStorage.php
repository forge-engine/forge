<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage;

use App\Modules\ForgeStorage\Contracts\StorageDriverInterface;
use App\Modules\ForgeStorage\Services\ProviderResolver;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;

#[Module(
  name: 'ForgeStorage',
  version: '1.1.0',
  description: 'Simple file upload storage module with multiple provider support',
  author: 'Forge Team',
  license: 'MIT',
  type: 'storage',
  tags: ['storage', 'file', 'upload']
)]
#[Service]
#[Compatibility(framework: '>=0.1.2', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[ConfigDefaults(defaults: [
  'forge_storage' => [
    'provider' => 'local',
    'max_size' => 10485760,
    'allowed_types' => '*',
    'hash_filenames' => true,
    'providers' => [
      'local' => [
        'root_path' => 'storage/app',
        'public_path' => 'public/storage',
      ],
      's3' => [
        'key' => null,
        'secret' => null,
        'region' => null,
        'bucket' => null,
        'endpoint' => null,
      ],
    ],
    'locations' => [],
  ]
])]
#[PostInstall(command: 'db:migrate', args: ['--type=module', '--module=forge-storage'])]
#[PostUninstall(command: 'db:migrate:rollback', args: ['--type=module', '--module=forge-storage'])]
final class ForgeStorage
{
  public function __construct(private Config $config)
  {
  }

  public function register(Container $container): void
  {
    $container->singleton(StorageDriverInterface::class, function (Container $c) {
      $providerResolver = $c->make(ProviderResolver::class);
      return $providerResolver->resolve();
    });
  }
}
