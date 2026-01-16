<?php

declare(strict_types=1);

namespace App\Modules\ForgeComponents;

use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(
  name: 'ForgeComponents',
  version: '0.2.0',
  description: 'Component library module that composes ForgeUi primitives',
  order: 99,
  author: 'Forge Team',
  license: 'MIT',
  type: 'generic',
  tags: ['generic', 'component', 'library', 'ui', 'component', 'library']
)]
#[Service(id: null, singleton: true)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
final class ForgeComponentsModule
{
  use OutputHelper;

  public function register(Container $container): void
  {
  }
}
