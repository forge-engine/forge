<?php
declare(strict_types=1);

namespace App\Modules\ExampleModule\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ExampleModule\Contracts\ExampleInterface;

#[Service]
#[Provides(interface: ExampleInterface::class, version: '0.1.0')]
#[Requires(interface: 'AnotherServiceInterface', version: '^0.1.0')]
final class ExampleService implements ExampleInterface
{
	public function __construct(/** AnotherServiceInterface $anotherService */)
	{
		
	}
	public function doSomething(): string
	{
		return "Doing something from the  Example module Service";
	}
}