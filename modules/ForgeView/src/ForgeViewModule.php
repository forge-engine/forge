<?php

declare(strict_types=1);

namespace App\Modules\ForgeView;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeView\Contracts\ForgeViewInterface;
use App\Modules\ForgeView\Services\ForgeViewService;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\Structure;

#[Module(
    name: 'ForgeView', 
    version: '0.1.0', 
    description: 'A View engine provided by forge', 
    order: 4, 
    author: 'Forge Team', 
    license: 'MIT', 
    type: 'core',
    tags: ['view-engine', 'view'])]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Provides(interface: ForgeViewInterface::class, version: '0.1.0')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]

#[Structure(structure: [
    'controllers' => 'src/Controllers',
    'services' => 'src/Services',
    'migrations' => 'src/Database/Migrations',
    'views' => 'src/Resources/views',
    'components' => 'src/Resources/components',
    'commands' => 'src/Commands',
    'events' => 'src/Events',
    'tests' => 'src/tests',
    'models' => 'src/Models',
    'dto' => 'src/Dto',
    'seeders' => 'src/Database/Seeders',
    'middlewares' => 'src/Middlewares',
])]

#[ConfigDefaults(defaults: [
    "forge_view" => []
])]
#[PostInstall(command: 'forge-view:greet', args: [])]
#[PostUninstall(command: 'forge-view:greet', args: ['--post-uninstall'])]
final class ForgeViewModule
{
    use OutputHelper;
    public function register(Container $container): void
    {
        $container->bind(ForgeViewInterface::class, ForgeViewService::class);
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
    	public function onAfterModuleRegister(): void
    	{
    		//error_log("ForgeView:  registered!");
    	}
}
