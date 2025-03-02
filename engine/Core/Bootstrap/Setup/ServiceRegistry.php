<?php

namespace Forge\Core\Bootstrap\Setup;

use Forge\Core\Contracts\Events\EventDispatcherInterface;
use Forge\Core\Contracts\Events\EventInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Events\EventDispatcher;
use Forge\Http\Session;
use Forge\Http\Middleware\MiddlewareInterface;


class ServiceRegistry
{
    public static function registerServices(Container $container): void
    {
        $container->singleton(Session::class, Session::class);
        $container->singleton(MiddlewareInterface::class, MiddlewareInterface::class);
        $container->singleton(EventDispatcherInterface::class, EventDispatcher::class);
    }
}