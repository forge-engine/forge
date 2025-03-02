<?php

namespace Forge\Modules\ForgeEvents\Command;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Helpers\App;
use Forge\Modules\ForgeEvents\Services\EventQueue;

class EventCommands implements CommandInterface
{
    public function getName(): string
    {
        return 'event:process';
    }

    public function getDescription(): string
    {
        return 'Process queued events';
    }

    public function execute(array $args): int
    {
        /** @var EventQueue $eventQueue */
        $eventQueue = App::getContainer()->get(EventQueue::class);
        $processed = $eventQueue->process();
        echo "Processed $processed events\n";
        return 0;
    }
}