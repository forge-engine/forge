<?php

declare(strict_types=1);

namespace App\Modules\ForgeUi;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;

#[Module(name: 'ForgeUi', description: 'A UI component module by forge.', order: 99, core: false, isCli: false)]
#[Provides(interface: ForgeUIModule::class, version: '0.1.1')]
#[Requires()]
#[Service(id: null, singleton: true)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ForgeUIModule
{
    use OutputHelper;
    public function register(Container $container): void
    {
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
    public function onAfterModuleRegister(): void
    {
        //error_log("ForgeUiComponents:  registered!");
    }
}
