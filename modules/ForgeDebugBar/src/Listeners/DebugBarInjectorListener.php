<?php

namespace App\Modules\ForgeDebugbar\Listeners;

use Forge\Core\Contracts\DebugBarInterface;

class DebugBarInjectorListener
{
    public function handle(mixed $event): void
    {
        // if ($event instanceof ResponseReadyForDebugBarInjection) {
        //     /** @var DebugBarInterface $debugBar */
        //     $debugBar = $event->container->get(DebugBarInterface::class);
        //     $debugBar->injectDebugBarIfEnabled($event->response, $event->container);
        // }
    }
}
