<?php

declare(strict_types=1);

namespace App\Modules\ForgeNexus;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeNexus\Contracts\ForgeNexusInterface;
use App\Modules\ForgeNexus\Services\ForgeNexusService;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(name: 'ForgeNexus', description: 'CMS for Forge Framework')]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ForgeNexusModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $container->bind(ForgeNexusInterface::class, ForgeNexusService::class);
    }
}
