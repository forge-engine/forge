<?php

namespace Forge\Modules\ForgePackageManager;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Modules\ForgePackageManager\Src\Commands\InstallCommand;
use Forge\Modules\ForgePackageManager\Src\Commands\RemoveModuleCommand;
use Forge\Modules\ForgePackageManager\Src\Commands\InstallModuleCommand;
use Forge\Modules\ForgePackageManager\Src\Services\PackageManager;
use Forge\Modules\ForgePackageManager\Src\Contracts\PackageManagerInterface;

class ForgePackageManagerModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        if (PHP_SAPI === 'cli') {
            $container->instance(PackageManagerInterface::class, PackageManager::class);
            $container->bind(CommandInterface::class, InstallCommand::class);
            $container->bind(CommandInterface::class, InstallModuleCommand::class);
            $container->bind(CommandInterface::class, RemoveModuleCommand::class);
            $container->tag(InstallCommand::class, ['module.command']);
            $container->tag(InstallModuleCommand::class, ['module.command']);
            $container->tag(RemoveModuleCommand::class, ["module.command"]);
        }
    }
}