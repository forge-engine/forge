<?php

namespace Forge\Modules\ForgeOrm;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Contracts\Modules\ForgeOrm\CollectionInterface;
use Forge\Core\Resources\CollectionResource;
use Forge\Core\Resources\ModelResource;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeOrm\Commands\MigrateCommand;
use Forge\Modules\ForgeOrm\Commands\MigrateRollbackCommand;
use Forge\Modules\ForgeOrm\Commands\SeedCommand;
use Forge\Modules\ForgeOrm\Contracts\ForgeOrmInterface;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\Debug;
use Forge\Modules\ForgeOrm\Migrations\MigrationManager;
use Forge\Modules\ForgeOrm\Schema\Schema;
use Forge\Modules\ForgeOrm\Seeder\SeedManager;

class ForgeOrmModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        $module = new ForgeOrm();
        $container->instance(ForgeOrmInterface::class, $module);

        $schema = new Schema();
        $container->instance(Schema::class, $schema);

        $queryBuilder = new QueryBuilder();
        $container->instance(QueryBuilder::class, $queryBuilder);

        // Resource registration
        $container->bind(CollectionResource::class, CollectionResource::class);
        $container->bind(ModelResource::class, ModelResource::class);
        $container->instance(CollectionInterface::class, Collection::class);

        if (PHP_SAPI === 'cli') {
            // Migration Manager
            $migrationManager = new MigrationManager(
                $container->get(DatabaseInterface::class),
                $container->get(Schema::class)
            );
//            $seederManager = new SeedManager(
//                $container->get(DatabaseInterface::class),
//                $container->get(Schema::class)
//            );

            $container->bind(MigrationManager::class, MigrationManager::class, true);


            // Migration Commands
            $container->bind(CommandInterface::class, MigrateCommand::class);
            $container->bind(CommandInterface::class, MigrateRollbackCommand::class);
            $container->tag(MigrateCommand::class, ['module.command']);
            $container->tag(MigrateRollbackCommand::class, ['module.command']);

            //$container->instance(SeedManager::class, $seederManager);
            // Seeder Commands
            //$container->bind(CommandInterface::class, SeedCommand::class);
            //$container->tag(SeedCommand::class, ['module.command']);
        }
    }
}

