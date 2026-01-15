<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(name: 'ForgeDeployment', version: '1.0.0', description: 'Deploy applications to cloud providers with automated provisioning', order: 99)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[ConfigDefaults(defaults: [
  "forge_deployment" => []
])]
final class ForgeDeploymentModule
{
  use OutputHelper;
  public function register(Container $container): void
  {

  }

  #[LifecycleHook(hook: LifecycleHookName::AFTER_MODULE_REGISTER)]
  public function onAfterModuleRegister(): void
  {
    //error_log("ForgeDeployment:  registered!");
  }
}
