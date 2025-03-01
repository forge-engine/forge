<?php

namespace Forge\Core\Bootstrap\Setup;

use Forge\Core\Bootstrap\Environment;
use Forge\Core\Configuration\Config;
use Forge\Core\DependencyInjection\Container;

class EnvironmentSetup
{
    public static function setup(Container $container, string $basePath): void
    {
        $env = new Environment($basePath);
        $container->instance(Environment::class, $env);

        $isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
        $config = new Config($basePath, $isProduction);
        $container->instance(Config::class, $config);

        if ($env->isLocal() || $env->isDevelopment()) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ERROR);
            ini_set('display_errors', '0');
        }
    }
}