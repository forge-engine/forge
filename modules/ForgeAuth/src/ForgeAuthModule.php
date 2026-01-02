<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeAuth\Contracts\ForgeAuthInterface;
use App\Modules\ForgeAuth\Services\ForgeAuthService;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(name: 'ForgeAuth', version: '0.1.9', description: 'An Auth module by forge.', order: 99)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[PostInstall(command: 'db:migrate', args: ['--type=', 'module', '--module=', 'ForgeAuth'])]
#[PostUninstall(command: 'db:migrate', args: ['--type=', 'module', '--module=', 'ForgeAuth'])]
final class ForgeAuthModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $container->bind(ForgeAuthInterface::class, ForgeAuthService::class);
    }
}
