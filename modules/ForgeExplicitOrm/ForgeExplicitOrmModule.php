<?php

namespace Forge\Modules\ForgeExplicitOrm;

use Forge\Modules\ForgeExplicitOrm\Contracts\ForgeExplicitOrmInterface;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\Debug;

class ForgeExplicitOrmModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        // Module registration logic here
        $module = new ForgeExplicitOrm();
        $container->instance(ForgeExplicitOrmInterface::class, $module);
        Debug::addEvent("[ForgeExplicitOrmModule] Registered", "start"); // Example event
    }
}