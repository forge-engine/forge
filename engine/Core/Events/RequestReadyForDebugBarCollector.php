<?php

namespace Forge\Core\Events;

use Forge\Core\Contracts\Events\EventInterface;
use Forge\Http\Request;
use Forge\Core\DependencyInjection\Container;

class RequestReadyForDebugBarCollector implements EventInterface
{
    public function __construct(
        public readonly Request   $request,
        public readonly Container $container
    )
    {
    }
}