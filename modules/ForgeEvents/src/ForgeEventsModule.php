<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents;

use App\Modules\ForgeEvents\Services\EventDispatcher;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(name: 'ForgeEvents', version: '0.2.1', description: 'An Event Queue system by forge', order: 12)]
#[Service]
#[Provides(EventDispatcher::class, version: '0.2.1')]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ForgeEventsModule
{
    use OutputHelper;
    public function register(Container $container): void
    {
    }
}
