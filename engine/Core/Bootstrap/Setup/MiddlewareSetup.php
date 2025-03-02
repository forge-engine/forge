<?php

namespace Forge\Core\Bootstrap\Setup;

use Forge\Core\Configuration\Config;
use Forge\Core\DependencyInjection\Container;
use Forge\Http\Middleware\MiddlewarePipeline;
use Forge\Http\Session;
use Forge\Core\Bootstrap\Bootstrap;


class MiddlewareSetup
{
    public static function setup(Container $container, bool $isCli, Bootstrap $bootstrapInstance): MiddlewarePipeline
    {
        $middlewareStack = new MiddlewarePipeline();
        $container->instance(MiddlewarePipeline::class, $middlewareStack);

        if (!$isCli) {
            self::registerMiddlewares($container, $middlewareStack);
        }
        return $middlewareStack;
    }

    private static function registerMiddlewares(Container $container, MiddlewarePipeline $pipeline): void
    {
        $config = $container->get(Config::class);
        $middlewares = $config->get('app.middleware', []);
        $session = $container->get(Session::class);

        foreach ($middlewares as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                $middlewareConfigKey = str_replace('\\', '.', strtolower(ltrim($middlewareClass, '\\')));
                $middlewareConfig = $config->get($middlewareConfigKey, []);
                $pipeline->add(new $middlewareClass($container, $session, $middlewareConfig));
            } else {
                error_log("Middleware class {$middlewareClass} not found.");
            }
        }
    }
}