<?php

namespace Forge\Modules\ForgeTest;

use Forge\Modules\ForgeTest\Contracts\ForgeTestInterface;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\Debug;

class ForgeTestModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        // Module registration logic here
        $module = new ForgeTest();
        $container->instance(ForgeTestInterface::class, $module);
        Debug::addEvent("[ForgeTestModule] Registered", "start"); // Example event
    }
}