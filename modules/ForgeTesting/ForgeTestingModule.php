<?php

namespace Forge\Modules\ForgeTesting;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Modules\ForgeTesting\Commands\TestRunCommand;

class ForgeTestingModule
{
    public function register(Container $container): void
    {
        if (PHP_SAPI === 'cli') {
            $container->bind(CommandInterface::class, TestRunCommand::class);
            $container->tag(TestRunCommand::class, ['module.command']);
        }
    }
}