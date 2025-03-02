<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\Contracts\Modules\ForgeEventDispatcherInterface;
use Forge\Core\Helpers\App;

class ForgeEventSubscriber
{
    public static function subscribe(array $subscriptions): void
    {
        $dispatcher = App::getContainer()->get(ForgeEventDispatcherInterface::class);

        foreach ($subscriptions as $event => $listeners) {
            foreach ((array)$listeners as $listener) {
                $dispatcher->listen($event, $listener);
            }
        }
    }
}