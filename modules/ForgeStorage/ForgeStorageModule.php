<?php

namespace Forge\Modules\ForgeStorage;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\App;
use Forge\Modules\ForgeStorage\Command\StorageCommands;
use Forge\Modules\ForgeStorage\Contracts\StorageInterface;
use Forge\Modules\ForgeStorage\Drivers\LocalDriver;
use Forge\Modules\ForgeStorage\Command\LinkStorageCommand;
use Forge\Modules\ForgeStorage\Command\UnlinkStorageCommand;

class ForgeStorageModule extends ModulesInterface
{
    public function register(Container $container): void
    {

        $container->instance(StorageInterface::class, function () {
            $driver = App::config()->get('forge_storage.default_driver', 'local');

            return match ($driver) {
                'local' => new LocalDriver(),
                default => throw new \RuntimeException("Unsupported storage driver: {$driver}")
            };
        });

        if (PHP_SAPI === 'cli') {
            $container->bind(CommandInterface::class, StorageCommands::class);
            $container->bind(CommandInterface::class, LinkStorageCommand::class);
            $container->bind(CommandInterface::class, UnlinkStorageCommand::class);

            $container->tag(LinkStorageCommand::class, ["module.command"]);
            $container->tag(UnlinkStorageCommand::class, ["module.command"]);
        }
    }
}