<?php

namespace Forge\Modules\ForgeEvents\Command;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Helpers\App;
use Forge\Modules\ForgeEvents\Services\EventQueue;

class QueueWorkerCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'event:worker';
    }

    public function getDescription(): string
    {
        return 'Start a worker to process queued events';
    }

    public function execute(array $args): int
    {
        /** @var EventQueue $eventQueue */
        $eventQueue = App::getContainer()->get(EventQueue::class);

        while (true) {
            $eventQueue->process();
            sleep(5);
        }

        return 0;
    }
}
