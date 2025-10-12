<?php

namespace App\Modules\ForgeDebugbar\Listeners;

use Forge\Core\Contracts\DebugBarInterface;
use Forge\Core\DI\Container;
use Forge\Modules\ForgeDebugbar\Collectors\RequestCollector;

class RequestCollectorListener
{
    public function handle(array $event): void
    {
        $container = Container::getInstance();
        /** @var DebugBarInterface $debugBar */
        $debugBar = $container->get(DebugBarInterface::class);
        $debugBar->addCollector('request', function () use ($event) {
            return RequestCollector::collect($event['request']);
        });
    }
}
