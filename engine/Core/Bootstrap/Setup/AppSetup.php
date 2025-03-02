<?php

namespace Forge\Core\Bootstrap\Setup;

use Forge\Core\DependencyInjection\Container;
use MyApp\MyApp;

class AppSetup
{
    public static function setup(Container $container, $appManager, &$apps): void
    {
        MyApp::init();
        $myApp = MyApp::getMyApp();
        $appManager->register($myApp);
        $apps = [$myApp];
        $appManager->bootApps($container);
    }
}