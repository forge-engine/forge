<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeBilling\Contracts\ForgeBillingInterface;
use App\Modules\ForgeBilling\Services\ForgeBillingService;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\Structure;

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
    'languages' => 'src/Languages',
])]


#[Module(name: 'ForgeBilling', version: '0.1.0', description: 'A billing portal and functionality provided by forge', order: 99, author: 'Your Name', license: 'MIT', tags: [])]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Provides(interface: ForgeBillingInterface::class, version: '0.1.0')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[ConfigDefaults(defaults: [
    "forge_billing" => []
])]
#[PostInstall(command: 'forge-billing:greet', args: [])]
#[PostUninstall(command: 'forge-billing:greet', args: ['--post-uninstall'])]
final class ForgeBillingModule
{
    use OutputHelper;
    public function register(Container $container): void
    {
        $container->bind(ForgeBillingInterface::class, ForgeBillingService::class);
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
    public function onAfterModuleRegister(): void
    {
        //error_log("ForgeBilling:  registered!");
    }
}
