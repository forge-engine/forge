<?php

declare(strict_types=1);

namespace App\Modules\ForgeLogger;

use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeLogger\Contracts\ForgeLoggerInterface;
use App\Modules\ForgeLogger\Services\ForgeLoggerService;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(
  name: 'ForgeLogger',
  version: '0.3.0',
  description: 'A logger by Forge.',
  order: 90,
  author: 'Forge Team',
  license: 'MIT',
  type: 'logging',
  tags: ['logging', 'logger', 'log', 'logging-system', 'logging-library', 'logging-framework']
)]
#[Service]
#[Provides(ForgeLoggerInterface::class, version: '0.3.0')]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[ConfigDefaults(defaults: [
  'forge_logger' => [
    'driver' => 'syslog',
    'path' => '/storage/logs/forge.log',
  ]
])]
final class ForgeLoggerModule
{
  use OutputHelper;

  public function register(Container $container): void
  {
    $this->setupConfigDefaults($container);
    $container->bind(ForgeLoggerInterface::class, ForgeLoggerService::class);
  }

  private function setupConfigDefaults(Container $container): void
  {
    /** @var Config $config */
    $config = $container->get(Config::class);
    $config->set('forge_logger.driver', env('FORGE_LOGGER_DRIVER', 'syslog'));
    $config->set('forge_logger.path', env('FORGE_LOGGER_PATH', BASE_PATH . '/storage/logs/forge.log'));
  }
}
