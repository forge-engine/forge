<?php

namespace Forge\Modules\ForgeQueue;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Helpers\App;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Modules\ForgeQueue\Commands\QueueCommand;

class ForgeQueueModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        $queue = new Queue(
            App::db(),
            []
        );
        $container->instance(Queue::class, $queue);

        if (PHP_SAPI === 'cli') {
            $container->instance(CommandInterface::class, QueueCommand::class);
            $container->tag(QueueCommand::class, ['module.command']);
        }
    }
}