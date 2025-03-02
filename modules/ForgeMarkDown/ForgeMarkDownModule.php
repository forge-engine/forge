<?php

namespace Forge\Modules\ForgeMarkDown;

use Forge\Core\Contracts\Modules\MarkDownInterface;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;

class ForgeMarkDownModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        $container->bind(MarkDownInterface::class, ForgeMarkDown::class);
    }
}