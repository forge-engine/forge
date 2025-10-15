<?php

declare(strict_types=1);

namespace App\Modules\ForgeLogger;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeLogger\Contracts\ForgeLoggerInterface;
use App\Modules\ForgeLogger\Services\ForgeLoggerService;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;
use const pcov\version;

#[Module(name: 'ForgeLogger', version: '0.1.1', description: 'A logger by Forge.', order: 90)]
#[Service]
#[Provides(ForgeLoggerInterface::class, version: '0.1.1')]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ForgeLoggerModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $container->bind(ForgeLoggerInterface::class, ForgeLoggerService::class);
    }
}
