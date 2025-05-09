<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\CLI\Traits\OutputHelper;

#[Module(name: 'ForgeEvents', description: 'An Event Queue system by forge', order: 99)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ForgeEventsModule
{
    use OutputHelper;
    public function register(Container $container): void
    {
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
    public function onAfterModuleRegister(): void
    {
        //error_log("ForgeEvents:  registered!");
    }
}
