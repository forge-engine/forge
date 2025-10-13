<?php

namespace App\Modules\ForgeStaticGen;

use App\Modules\ForgeMarkDown\Contracts\ForgeMarkDownInterface;
use App\Modules\ForgeStaticGen\Contracts\ForgeStaticGenInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;

#[Service()]
#[Module(name: 'ForgeStaticGen', description: "A Basic Static Site Generator by Forge", version: "0.1.1", isCli: true)]
#[Requires(interface: ForgeMarkDownInterface::class, version: "0.1.0")]
#[Compatibility(framework: ">=0.1.0", php: ">=8.3")]
#[Provides(interface:ForgeStaticGenInterface::class, version: "0.1.0")]
#[ConfigDefaults(defaults: [])]
class ForgeStaticGenModule
{
    public function register(Container $container): void
    {
        $mdParser = $container->get(ForgeMarkDownInterface::class);
        $module = new ForgeStaticGen($mdParser, 'public/static');
        $container->bind(ForgeStaticGenInterface::class, function () use ($module) {
            return $module;
        });

        $container->bind(LayoutBuilder::class, LayoutBuilder::class);
    }
}
