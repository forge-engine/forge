<?php

namespace Forge\Modules\ForgeDatabase;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Helpers\App;
use Forge\Modules\ForgeDatabase\Adapters\InMemoryAdapter;
use Forge\Modules\ForgeDatabase\Adapters\MysqlAdapter;
use Forge\Modules\ForgeDatabase\Adapters\PostgresqlAdapter;
use Forge\Modules\ForgeDatabase\Adapters\RedisAdapter;
use Forge\Modules\ForgeDatabase\Adapters\SqliteAdapter;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Modules\ForgeDatabase\Commands\ResetDatabaseCommand;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;

class DatabaseModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        $config = App::config();
        $databaseParams = $config->get('database');
        $connection = $databaseParams['connections'][$databaseParams['default']];
        $connectionName = $connection['database'];

        $adapter = match ($connection['driver']) {
            'mysql' => new MysqlAdapter($container, $connectionName),
            'pgsql' => new PostgresqlAdapter($container, $connectionName),
            'sqlite' => new SqliteAdapter($container, $connectionName),
            'memory' => new InMemoryAdapter($container, $connectionName),
            'redis' => new RedisAdapter(),
            default => throw new \RuntimeException("Unsupported driver")
        };

        $adapter->connect($connection);
        $container->instance(DatabaseInterface::class, $adapter);

        if (PHP_SAPI === 'cli') {
            $container->instance(CommandInterface::class, ResetDatabaseCommand::class);
            $container->tag(ResetDatabaseCommand::class, ["module.command"]);
        }
    }
}