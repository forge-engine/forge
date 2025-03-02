<?php

namespace Forge\Core\Bootstrap\Setup;

use Forge\Core\Bootstrap\AppManager;
use Forge\Core\DependencyInjection\Container;

class AppManagerSetup
{
    public static function setup(Container $container): AppManager
    {
        AppManager::init();
        $appManager = AppManager::getAppManager();
        $container->instance(AppManager::class, $appManager);
        return $appManager;
    }
}