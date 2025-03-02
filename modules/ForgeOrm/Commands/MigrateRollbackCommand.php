<?php

namespace Forge\Modules\ForgeOrm\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Helpers\App;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgeOrm\Migrations\MigrationManager;

class MigrateRollbackCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'migrate:rollback';
    }

    public function getDescription(): string
    {
        return 'Run all pending database migrations';
    }

    public function execute(array $args): int
    {
        /** @var MigrationManager $migrationManager */
        $container = App::getContainer();
        $migrationManager = $container->get(MigrationManager::class);

        try {
            $migrationManager->rollbackLastMigration();
            $this->success("Migrations rolled back successfully");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Migration failed: " . $e->getMessage());
            return 1;
        }
    }
}