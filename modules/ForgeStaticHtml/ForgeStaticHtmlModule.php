<?php

namespace Forge\Modules\ForgeStaticHtml;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\App;
use Forge\Modules\ForgeStaticHtml\Command\GenerateStaticCommand;

class ForgeStaticHtmlModule
{
    public function register(Container $container): void
    {
        if (PHP_SAPI === 'cli') {
            $container->bind(CommandInterface::class, GenerateStaticCommand::class);
            $container->tag(GenerateStaticCommand::class, ["module.command"]);
        }
    }
}