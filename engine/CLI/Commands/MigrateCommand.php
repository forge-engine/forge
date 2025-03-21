<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\Core\Database\Migrator;

class MigrateCommand extends Command
{
    public function __construct(private Migrator $migrator) {}
    public static function getName(): string
    {
        return "migrate";
    }
    public static function getDescription(): string
    {
        return "Run database migrations";
    }

    public function execute(array $args): int
    {
        $this->migrator->run();
        echo "Migrations completed successfully\n";
        return 0;
    }
}
