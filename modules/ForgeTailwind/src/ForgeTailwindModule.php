<?php

declare(strict_types=1);

namespace App\Modules\ForgeTailwind;

use App\Modules\ForgeTailwind\Contracts\ForgeTailwindInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(name: 'ForgeTailwind', version: '0.1.0', description: 'A tailwind module by forge', order: 99, isCli: true)]
#[Service]
#[Provides(interface: ForgeTailwindInterface::class, version: '0.1.0')]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ForgeTailwindModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
    }
}
