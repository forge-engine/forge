<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage;

use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use App\Modules\ForgeStorage\Contracts\StorageInterface;
use App\Modules\ForgeStorage\Drivers\LocalDriver;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\LifecycleHookName;

#[Module(name: 'ForgeStorage', description: 'A Forge Storage Solution by forge team.')]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
class ForgeStorage
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

    #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
    public function onAfterModuleRegister(): void
    {
        //error_log("ForgeStorage: registered!");
    }
}
