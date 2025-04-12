<?php

declare(strict_types=1);

namespace App\Modules\ForgeWelcome;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeWelcome\Contracts\ForgeWelcomeInterface;
use App\Modules\ForgeWelcome\Services\ForgeWelcomeService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\CLI\Traits\OutputHelper;

#[Module(name: 'ForgeWelcome', description: 'A playground by forge', order: 99)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ForgeWelcomeModule
{
	use OutputHelper;
	public function register(Container $container): void
	{
		$container->bind(ForgeWelcomeInterface::class, ForgeWelcomeService::class);
	}

	#[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
	public function onAfterModuleRegister(): void
	{
		//error_log("ForgeWelcome:  registered!");
	}
}
