<?php

namespace Forge\Modules\ForgeDebugbar;

use Forge\Core\Contracts\Events\EventDispatcherInterface;
use Forge\Core\Contracts\Modules\DebugBarInterface;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Modules\ForgeDebugbar\Collectors\DatabaseCollector;
use Forge\Modules\ForgeDebugbar\Collectors\ExceptionCollector;
use Forge\Modules\ForgeDebugbar\Collectors\MemoryCollector;
use Forge\Modules\ForgeDebugbar\Collectors\MessageCollector;
use Forge\Modules\ForgeDebugbar\Collectors\RouteCollector;
use Forge\Modules\ForgeDebugbar\Collectors\SessionCollector;
use Forge\Modules\ForgeDebugbar\Collectors\TimeCollector;
use Forge\Modules\ForgeDebugbar\Collectors\TimelineCollector;
use Forge\Modules\ForgeDebugbar\Collectors\ViewCollector;
use Forge\Modules\ForgeDebugbar\Listeners\DatabaseQueryListener;
use Forge\Modules\ForgeDebugbar\Listeners\DebugBarInjectorListener;
use Forge\Modules\ForgeDebugbar\Listeners\RequestCollectorListener;
use Forge\Core\Events\DatabaseQueryExecuted;
use Forge\Core\Events\RequestReadyForDebugBarCollector;
use Forge\Core\Events\ResponseReadyForDebugBarInjection;

class DebugBarModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        $debugBarInstance = new DebugBar();
        $container->instance(DebugBarInterface::class, $debugBarInstance);
    }

    public function onAfterModuleRegister(Container $container): void
    {
        /** @var DebugBarInterface $debugBarInstance */
        $debugBarInstance = $container->get(DebugBarInterface::class);
        $debugBarInstance->addCollector('messages', [MessageCollector::class, 'collect']);
        $debugBarInstance->addCollector('exceptions', [ExceptionCollector::class, 'collect']);
        $debugBarInstance->addCollector('time', [TimeCollector::class, 'collect']);
        $debugBarInstance->addCollector('memory', [MemoryCollector::class, 'collect']);
        $debugBarInstance->addCollector('session', [SessionCollector::class, 'collect']);
        $debugBarInstance->addCollector('views', [ViewCollector::class, 'collect']);
        $debugBarInstance->addCollector('timeline', [TimelineCollector::class, 'collect']);
        $debugBarInstance->addCollector('route', [RouteCollector::class, 'collect']);
        $debugBarInstance->addCollector('database', [DatabaseCollector::class, 'collect']);

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $container->get(EventDispatcherInterface::class);

        if ($container->has(DebugBarInterface::class)) {
            $debugBarInterface = $container->get(DebugBarInterface::class);
            $databaseQueryListener = new DatabaseQueryListener($debugBarInterface);
            $eventDispatcher->listen(DatabaseQueryExecuted::class, $databaseQueryListener);
        }

        $eventDispatcher->listen(ResponseReadyForDebugBarInjection::class, DebugBarInjectorListener::class);
        $eventDispatcher->listen(RequestReadyForDebugBarCollector::class, RequestCollectorListener::class);
    }
}