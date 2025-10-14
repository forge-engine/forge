<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeMultiTenant\Contracts\ForgeMultiTenantInterface;

#[Service]
#[Provides(interface: ForgeMultiTenantInterface::class, version: '0.1.0')]
#[Requires]
final class ForgeMultiTenantService implements ForgeMultiTenantInterface
{
	public function __construct(/** private AnotherServiceInterface $anotherService */)
	{
		
	}
	public function doSomething(): string
	{
		return "Doing something from the  Example module Service";
	}
}