<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeAuth\Contracts\ForgeAuthInterface;
use App\Modules\ForgeAuth\Contracts\UserRepositoryInterface;
use App\Modules\ForgeAuth\Repositories\UserRepository;
use App\Modules\ForgeAuth\Services\ForgeAuthService;
use App\Modules\ForgeSqlOrm\ORM\Cache\QueryCache;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\Structure;

#[Service]
#[Module(
  name: 'ForgeAuth',
  version: '0.4.0',
  description: 'An Auth module by forge.',
  order: 99,
  author: 'Forge Team',
  license: 'MIT',
  type: 'auth',
  tags: ['auth', 'authentication', 'authorization', 'authentication-system', 'authentication-library', 'authentication-framework']
)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[Structure(structure: [
    'controllers' => 'src/Controllers',
    'services' => 'src/Services',
    'migrations' => 'src/Database/Migrations',
    'views' => 'src/Resources/views',
    'components' => 'src/Resources/components',
    'commands' => 'src/Commands',
    'events' => 'src/Events',
    'tests' => 'src/tests',
    'models' => 'src/Models',
    'dto' => 'src/Dto',
    'seeders' => 'src/Database/Seeders',
    'middlewares' => 'src/Middlewares',
])]
#[PostInstall(command: 'db:migrate', args: ['--type=', 'module', '--module=', 'ForgeAuth'])]
#[PostUninstall(command: 'db:migrate', args: ['--type=', 'module', '--module=', 'ForgeAuth'])]
final class ForgeAuthModule
{
  use OutputHelper;

  public function register(Container $container): void
  {
    $container->bind(ForgeAuthInterface::class, ForgeAuthService::class);
    $container->bind(UserRepositoryInterface::class, function ($c) {
      return new UserRepository($c->get(QueryCache::class));
    });
  }
}
