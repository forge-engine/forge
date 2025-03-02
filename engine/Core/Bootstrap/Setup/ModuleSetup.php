<?php

namespace Forge\Core\Bootstrap\Setup;

use Forge\Core\DependencyInjection\Container;
use Forge\Core\Bootstrap\AppManager;
use Forge\Core\Module\ModuleLoader;

class ModuleSetup
{
    public static function setup(Container $container, AppManager $appManager, &$modules): void
    {
        $moduleLoader = ModuleLoader::getInstance($container, $appManager);
        $modules = $moduleLoader->getModules();
    }
}