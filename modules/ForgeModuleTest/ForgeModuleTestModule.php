<?php

namespace Forge\Modules\ForgeModuleTest;

use Forge\Modules\ForgeModuleTest\Contracts\ForgeModuleTestInterface;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\Debug;

class ForgeModuleTestModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        // Module registration logic here
        $module = new ForgeModuleTest();
        $container->instance(ForgeModuleTestInterface::class, $module);
        Debug::addEvent("[ForgeModuleTestModule] Registered", "start"); // Example event
    }
}