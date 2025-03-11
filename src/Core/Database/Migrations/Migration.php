<?php

declare(strict_types=1);

namespace Forge\Core\Database\Migrations;

use Forge\Core\Database\Connection;
use PDOException;

abstract class Migration
{
    public function __construct(protected Connection $pdo)
    {
        $this->pdo = $pdo;
    }

    abstract public function up(): void;
    abstract public function down(): void;

    protected function execute(string $sql): void
    {
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new MigrationException(
                "Migration failed: " . $e->getMessage(),
                $sql
            );
        }
    }
}
