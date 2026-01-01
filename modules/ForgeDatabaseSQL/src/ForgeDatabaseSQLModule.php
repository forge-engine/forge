<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL;

use App\Modules\ForgeDatabaseSQL\DB\DatabaseSetup;
use Forge\Core\Config\Environment;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Requires;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(name: 'ForgeDatabaseSQL', version: '0.1.1', description: 'SQL database support (SQLite, MySQL, PostgreSQL)',
    order: 0, type: 'core')]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Requires(interface: DatabaseConnectionInterface::class, version: '>=0.1.0')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[Provides(interface: 'forge-database-sql', version: '0.1.1')]
#[ConfigDefaults(defaults: [
    "forge_database_sql" => []
])]
#[PostInstall(command: 'forge-database-sql:greet', args: [])]
#[PostUninstall(command: 'forge-database-sql:greet', args: ['--post-uninstall'])]
final class ForgeDatabaseSQLModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        $env = Environment::getInstance();
        DatabaseSetup::setup($container, $env);
    }

}
