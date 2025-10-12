<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeNotification\Contracts\ForgeNotificationInterface;
use App\Modules\ForgeNotification\Services\ForgeNotificationService;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(name: 'ForgeNotification', description: 'A notification channel by forge', order: 99)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ForgeNotificationModule
{
    use OutputHelper;
    public function register(Container $container): void
    {
        $container->bind(ForgeNotificationInterface::class, ForgeNotificationService::class);
    }
}
