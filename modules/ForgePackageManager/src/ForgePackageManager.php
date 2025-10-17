<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager;

use App\Modules\ForgePackageManager\Contracts\PackageManagerInterface;
use App\Modules\ForgePackageManager\Services\PackageManagerService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;

#[Module(name: 'ForgePackageManager', version: '0.1.3', description: 'A Package Manager By Forge', order: 0, isCli: true)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[ConfigDefaults(defaults: [
    'forge_package_manager' => [
        'registry' => [],
        'cache_ttl' => 3600
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
}
