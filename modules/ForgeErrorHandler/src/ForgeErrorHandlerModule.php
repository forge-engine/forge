<?php

declare(strict_types=1);

namespace App\Modules\ForgeErrorHandler;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeErrorHandler\Contracts\ForgeErrorHandlerInterface;
use App\Modules\ForgeErrorHandler\Services\ForgeErrorHandlerService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\CLI\Traits\OutputHelper;

#[Module(name: 'ForgeErrorHandler', description: 'An error handler by Forge', order: 2, core: true)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ForgeErrorHandlerModule
{
    use OutputHelper;
    public function register(Container $container): void
    {
        $container->bind(ForgeErrorHandlerInterface::class, ForgeErrorHandlerService::class);
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
    public function onAfterModuleRegister(): void
    {
        //error_log("ForgeErrorHandler:  registered!");
    }
}
