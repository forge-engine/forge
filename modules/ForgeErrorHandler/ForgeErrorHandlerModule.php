<?php

namespace Forge\Modules\ForgeErrorHandler;

use Forge\Core\Contracts\Modules\ErrorHandlerInterface;
use Forge\Core\Helpers\App;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;

class ForgeErrorHandlerModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        if (!PHP_SAPI === 'cli') {
            $container->instance(ErrorHandlerInterface::class, function () {
                $config = App::config()->get('forge_error_handler');
                return new ErrorHandler(
                    debugMode: $config['debugMode'],
                    logPath: $config['logPath']
                );
            });
        }
    }
}