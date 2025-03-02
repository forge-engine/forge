<?php

namespace Forge\Core\Traits;

use Forge\Core\DependencyInjection\Container;

trait HasContainer
{
    protected Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
}
