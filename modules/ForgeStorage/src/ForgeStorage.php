<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage;

use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use App\Modules\ForgeStorage\Contracts\StorageInterface;
use App\Modules\ForgeStorage\Drivers\LocalDriver;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;

#[Module(name: 'ForgeStorage', description: 'A Forge Storage Solution by forge team.')]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[ConfigDefaults(defaults: [
    'forge_storage' => [
        'default_driver' => 'local',
        'hash_filenames' => true,
        'root_path' => 'storage/app',
        'public_path' => 'public/storage',
        'buckets' => [
            'uploads' => [
                'driver' => 'local',
                'public' => false,
                'expire' => 3600
            ]
        ]
    ]
])]
#[PostInstall(command: 'migrate', args: ['--type=module', '--module=forge-storage'])]
#[PostUninstall(command: 'migrate:rollback', args: ['--type=module', '--module=forge-storage'])]
readonly class ForgeStorage
{
    public function __construct(private Config $config)
    {
    }

    public function register(Container $container): void
    {
        $container->singleton(StorageInterface::class, function () {
            $driver = $this->config->get('forge_storage.default_driver', 'local');

            return match ($driver) {
                'local' => new LocalDriver(),
                default => throw new \RuntimeException("Unsupported storage driver: {$driver}")
            };
        });
    }
}
