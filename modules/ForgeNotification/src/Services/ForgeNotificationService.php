<?php
declare(strict_types=1);

namespace App\Modules\ForgeNotification\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeNotification\Contracts\ForgeNotificationInterface;

#[Service]
#[Provides(interface: ForgeNotificationInterface::class, version: '0.1.0')]
#[Requires]
final class ForgeNotificationService implements ForgeNotificationInterface
{
	public function __construct(/** private AnotherServiceInterface $anotherService */)
	{
		
	}
	public function doSomething(): string
	{
		return "Doing something from the  Example module Service";
	}
}