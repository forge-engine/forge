<?php

namespace Forge\Modules\ForgeDebugbar\Listeners;

use Forge\Core\Contracts\Events\EventInterface;
use Forge\Core\Contracts\Events\ListenerInterface;
use Forge\Core\Contracts\Modules\DebugBarInterface;
use Forge\Core\Events\ResponseReadyForDebugBarInjection;

class DebugBarInjectorListener implements ListenerInterface
{
    public function handle(EventInterface $event): void
    {
        if ($event instanceof ResponseReadyForDebugBarInjection) {
            /** @var DebugBarInterface $debugBar */
            $debugBar = $event->container->get(DebugBarInterface::class);
            $debugBar->injectDebugBarIfEnabled($event->response, $event->container);
        }
    }
}