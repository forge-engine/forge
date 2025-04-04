<?php

namespace Forge\Modules\ForgeDebugbar\Listeners;

use Forge\Core\Contracts\Events\EventInterface;
use Forge\Core\Contracts\Events\ListenerInterface;
use Forge\Core\Contracts\Modules\DebugBarInterface;
use Forge\Modules\ForgeDebugbar\Collectors\RequestCollector;
use Forge\Core\Events\RequestReadyForDebugBarCollector;

class RequestCollectorListener implements ListenerInterface
{

    /**
     * Handle the dispatched event.
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function handle(EventInterface $event): void
    {
        if ($event instanceof RequestReadyForDebugBarCollector) {
            /** @var DebugBarInterface $debugBar */
            $debugBar = $event->container->get(DebugBarInterface::class);
            $debugBar->addCollector('request', function () use ($event) {
                return RequestCollector::collect($event->request);
            });
        }
    }
}