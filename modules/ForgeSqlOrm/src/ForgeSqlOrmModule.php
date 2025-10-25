<?php

declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm;

use App\Modules\ForgeSqlOrm\ORM\QueryBuilder;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Debug\Metrics;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Module(name: 'ForgeSqlOrm', version: '0.1.0', description: 'SQL ORM Support (SQLite, MySQL, PostgreSQL)', order: 1,
    type: 'core')]
#[Service]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[ConfigDefaults(defaults: [
    "forge_sql_orm" => []
])]
final class ForgeSqlOrmModule
{
    use OutputHelper;

    public function register(Container $container): void
    {
        Metrics::start('query_builder_resolution');
        $container->bind(id: QueryBuilderInterface::class, concrete: function ($c) {
            return new QueryBuilder($c->get(DatabaseConnectionInterface::class));
        });
        Metrics::stop('query_builder_resolution');
    }

}
