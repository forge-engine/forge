<?php

declare(strict_types=1);

namespace App\Modules\ForgeUi;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;

#[Module(name: 'ForgeUi', version: '0.1.4', description: 'A UI component module by forge.', order: 99, core: false,
    isCli: false)]
#[Provides(interface: ForgeUIModule::class, version: '0.1.3')]
#[Requires()]
#[Service(id: null, singleton: true)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[PostInstall(command: 'asset:link', args: ['--type=module', '--module=forge-ui'])]
#[PostInstall(command: 'asset:unlink', args: ['--type=module', '--module=forge-ui'])]
final class ForgeUIModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
    }
}
