<?php

declare(strict_types=1);

namespace App\Modules\ExampleModule;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ExampleModule\Contracts\ExampleInterface;
use App\Modules\ExampleModule\Services\ExampleService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\HookManager;
use Forge\Core\Module\LifecycleHookName;

#[Module(name: 'ExampleModule', description: 'An example module for demostration purporse', order: 99)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ExampleModule
{
    public function register(Container $container): void
    {
        $container->bind(ExampleInterface::class, ExampleService::class);
        HookManager::addHook(LifecycleHookName::BEFORE_REQUEST, [$this, 'onBeforeRequest']);
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
    public function onAfterModuleRegister(): void
    {
        //error_log("[ForgeExampleModule]: After Module register");
    }

    #[LifecycleHook(hook: LifecycleHookName::BEFORE_REQUEST)]
    public function onBeforeRequest(): void
    {
        //error_log("[ForgeExampleModule]: on before request");
    }
}
