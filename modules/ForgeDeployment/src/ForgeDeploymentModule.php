<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment;

use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\LifecycleHookName;
use Forge\Core\Module\Attributes\HubItem;
use Forge\Core\Module\ForgeIcon;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(
  name: 'ForgeDeployment',
  version: '2.4.0',
  description: 'Deploy applications to cloud providers with automated provisioning',
  order: 99,
  author: 'Forge Team',
  license: 'MIT',
  type: 'deployment',
  tags: ['deployment', 'cloud', 'provider', 'automated', 'provisioning', 'deployment-system', 'deployment-library', 'deployment-framework']
)]
#[HubItem(label: 'Deployment', route: '/hub/deployment', icon: ForgeIcon::DEPLOY, order: 10)]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[ConfigDefaults(defaults: [
  'forge_deployment' => [
    'digitalocean' => [
      'api_token' => '',
    ],
    'cloudflare' => [
      'api_token' => '',
    ],
  ]
])]
final class ForgeDeploymentModule
{
  use OutputHelper;
  public function register(Container $container): void
  {
    $this->setupConfigDefaults($container);
  }

  private function setupConfigDefaults(Container $container): void
  {
    /** @var Config $config */
    $config = $container->get(Config::class);
    $config->set('forge_deployment.digitalocean.api_token', env('FORGE_DEPLOYMENT_DIGITALOCEAN_API_TOKEN'));
    $config->set('forge_deployment.cloudflare.api_token', env('FORGE_DEPLOYMENT_CLOUDFLARE_API_TOKEN'));
  }


}
