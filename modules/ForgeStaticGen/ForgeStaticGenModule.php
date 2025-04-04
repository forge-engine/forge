<?php

namespace Forge\Modules\ForgeStaticGen;

use Forge\Core\Contracts\Modules\MarkDownInterface;
use Forge\Modules\ForgeStaticGen\Commands\StaticGenBuildCommand;
use Forge\Modules\ForgeStaticGen\Contacts\ForgeStaticGenInterface;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;

class ForgeStaticGenModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        $mdParser = $container->get(MarkDownInterface::class);
        $module = new ForgeStaticGen($mdParser, 'public/static');
        $container->instance(ForgeStaticGenInterface::class, $module);

        $container->bind(LayoutBuilder::class, LayoutBuilder::class);


        if (PHP_SAPI === 'cli') {
            $staticBuildCommnad = new StaticGenBuildCommand($container);
            $container->instance(StaticGenBuildCommand::class, $staticBuildCommnad);
            $container->tag(StaticGenBuildCommand::class, ['module.command']);
        }
    }

}