<?php

namespace Forge\Modules\ForgeLogger;

use Forge\Core\Contracts\Modules\LoggerInterface;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\Configuration\Config;
use Forge\Core\DependencyInjection\Container;

class LoggerModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        $config = $container->get(Config::class);
        $logger = new FileLogger($config);
        $container->instance(LoggerInterface::class, $logger);
    }
}
