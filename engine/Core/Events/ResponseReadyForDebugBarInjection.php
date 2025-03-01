<?php

namespace Forge\Core\Events;

use Forge\Core\Contracts\Events\EventInterface;
use Forge\Http\Response;
use Forge\Core\DependencyInjection\Container;

class ResponseReadyForDebugBarInjection implements EventInterface
{
    public function __construct(
        public readonly Response  $response,
        public readonly Container $container
    )
    {
    }
}