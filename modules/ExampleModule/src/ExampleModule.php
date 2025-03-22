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
use Forge\Core\Module\LifecycleHookName;

#[Module(name: 'ExampleModule', description: 'An example module for demostration purporse')]
#[Service]
#[Compatibility(framework: '^0.1.0', php: '>=8.2')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ExampleModule
{
    public function register(Container $container): void
    {
        $container->bind(ExampleInterface::class, ExampleService::class);
    }

    #[LifecycleHook(hook: LifecycleHookName::APP_BOOTED)]
    public function onAppBooted(): void
    {
        error_log("ExampleModule: Application has booted!");
    }
}
