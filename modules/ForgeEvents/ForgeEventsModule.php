<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Contracts\Modules\ForgeEventDispatcherInterface;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\Debug;
use Forge\Modules\ForgeEvents\Command\EventCommands;
use Forge\Modules\ForgeEvents\Command\QueueWorkerCommand;

class ForgeEventsModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        $container->bind(ForgeEventDispatcherInterface::class, ForgeEventDispatcher::class, true);
        $container->bind(ForgeEventSubscriber::class, ForgeEventSubscriber::class);
        $container->bind('events', ForgeEventDispatcherInterface::class);

        if (PHP_SAPI === 'cli') {
            $container->bind(CommandInterface::class, EventCommands::class);
            $container->bind(CommandInterface::class, QueueWorkerCommand::class);
            $container->tag(EventCommands::class, ["module.command"]);
            $container->tag(QueueWorkerCommand::class, ["module.command"]);
        }
    }
}