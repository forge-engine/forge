<?php

namespace Forge\Core\Bootstrap\Setup;

use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\App;
use Forge\Core\Bootstrap\Bootstrap;

class ErrorHandlingSetup
{
    public static function setupErrorHandling(Container $container, bool $isCli, Bootstrap $bootstrapInstance): void
    {
        if (!$isCli) {
            if (App::isErrorHandlerEnabled()) {
                set_exception_handler([$bootstrapInstance, 'handleException']);
                set_error_handler([$bootstrapInstance, 'handleError'], E_ALL);
            }
        }
    }
}