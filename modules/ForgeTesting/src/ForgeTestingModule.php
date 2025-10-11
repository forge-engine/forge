<?php

declare(strict_types=1);

namespace App\Modules\ForgeTesting;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Requires;
use Forge\Core\Module\LifecycleHookName;

#[
    Module(
        name: "ForgeTesting",
        description: "A Test Suite Module By Forge",
        order: 9999,
        isCli: true,
        version: "0.1.1",
    ),
]
#[Service]
#[Compatibility(framework: ">=0.1.20", php: ">=8.3")]
#[Repository(type: "git", url: "https://github.com/forge-engine/modules")]
#[Provides(interface: TestCase::class, version: "0.1.1")]
#[Requires]
final class ForgeTestingModule
{
    public function register(Container $container): void
    {
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
    public function onAfterModuleRegister(): void
    {
    }
}
