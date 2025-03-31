<?php
declare(strict_types=1);

namespace App\Modules\ForgeNexus\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeNexus\Contracts\ForgeNexusInterface;

#[Service]
#[Provides(interface: ForgeNexusInterface::class, version: '0.1.0')]
#[Requires]
final class ForgeNexusService implements ForgeNexusInterface
{
	public function __construct(/** private AnotherServiceInterface $anotherService */)
	{
		
	}
	public function doSomething(): string
	{
		return "Doing something from the  Example module Service";
	}
}