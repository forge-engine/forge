<?php

namespace Forge\Core\Events;

use Forge\Core\Bootstrap\Bootstrap;
use Forge\Core\Contracts\Events\EventInterface;
use Forge\Core\DependencyInjection\Container;

class ErrorHandlingSetupEvent implements EventInterface
{
    public function __construct(
        public readonly Container $container,
        public readonly bool      $isCli,
        public readonly Bootstrap $bootstrapInstance
    )
    {
    }
}