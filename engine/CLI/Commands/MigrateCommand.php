<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\Core\Database\Migrator;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'migrate', description: 'Run database migrations')]
class MigrateCommand extends Command
{
    public function __construct(private Migrator $migrator) {}

    public function execute(array $args): int
    {
        $this->migrator->run();
        echo "Migrations completed successfully\n";
        return 0;
    }
}
