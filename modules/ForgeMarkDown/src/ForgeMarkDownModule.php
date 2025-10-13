<?php

namespace App\Modules\ForgeMarkDown;

use App\Modules\ForgeMarkDown\Contracts\ForgeMarkDownInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;

#[Module(name: 'ForgeMarkDown', description: 'A markdown processor', order: 70)]
#[Service()]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Provides(interface: ForgeMarkDownInterface::class, version: '0.1.0')]
class ForgeMarkDownModule
{
    public function register(Container $container): void
    {
        $container->bind(ForgeMarkDownInterface::class, ForgeMarkDown::class);
    }
}
