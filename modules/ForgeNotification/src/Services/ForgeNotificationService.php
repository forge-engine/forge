<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeNotification\Contracts\ForgeNotificationInterface;
use Forge\Core\Config\Config;

#[Service]
#[Provides(interface: ForgeNotificationInterface::class, version: '0.1.0')]
#[Requires]
final class ForgeNotificationService implements ForgeNotificationInterface
{
    public function __construct(private Config $config)
    {
    }
    public function send(string $channel, array $data): bool
    {
        return true;
    }
}
