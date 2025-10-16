<?php

declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\CLI\Traits\OutputHelper;
use App\Modules\ForgeMultiTenant\Services\TenantManager;
use ReflectionException;

#[Module(name: 'ForgeMultiTenant', version: '0.1.3', description: 'A Multi Tenant Module by Forge', order: 0)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[ConfigDefaults(defaults: [
    "forge_multi_tenant" => []
])]
#[PostInstall(command: 'migrate', args: ['--type=', 'module', '--module=', 'ForgeMultiTenant'])]
#[PostInstall(command: 'seed', args: ['--type=', 'module', '--module=', 'ForgeMultiTenant'])]
#[PostUninstall(command: 'migrate:rollback', args: ['--type=module', '--module=ForgeMultiTenant'])]
#[PostUninstall(command: 'seed:rollback', args: ['--type=module', '--module=ForgeMultiTenant'])]
final class ForgeMultiTenantModule
{
	use OutputHelper;
	public function register(Container $container): void
	{
        $container->bind(TenantManager::class, function(Container $container) {
            return new TenantManager($container);
        });
	}

	#[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
	public function onAfterModuleRegister(): void
	{

	}

}
