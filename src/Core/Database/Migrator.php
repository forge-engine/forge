<?php

declare(strict_types=1);

namespace Forge\Core\Database;

use Forge\Core\Database\Connection;
use Forge\Core\Database\Migrations\Migration;
use PDO;

final class Migrator
{
    private const MIGRATIONS_TABLE = "forge_migrations";

    public function __construct(private Connection $connection)
    {
        $this->ensureMigrationsTable();
    }

    private function getPdo(): PDO
    {
        return $this->connection->getPdo();
    }

    public function run(): void
    {
        $this->connection->beginTransaction();
        try {
            foreach ($this->getPendingMigrations() as $migration) {
                $this->runMigration($migration);
            }
            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function rollback(int $steps = 1): void
    {
        $this->connection->beginTransaction();
        try {
            $migrations = $this->getRanMigrations($steps);

            foreach ($migrations as $migration) {
                $this->rollbackMigration($migration);
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    private function ensureMigrationsTable(): void
    {
        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS " .
                self::MIGRATIONS_TABLE .
                " (
                migration VARCHAR(255) PRIMARY KEY,
                batch INT NOT NULL
            )"
        );
    }

    private function getPendingMigrations(): array
    {
        $ran = $this->getRanMigrationNames();
        $files = glob(BASE_PATH . "/app/database/migrations/*.php");

        return array_filter($files, function ($file) use ($ran) {
            return !in_array(basename($file), $ran);
        });
    }

    private function getRanMigrations(int $steps): array
    {
        $batch = $this->getLastBatch() - $steps + 1;

        $stmt = $this->connection->prepare(
            "SELECT migration FROM " .
                self::MIGRATIONS_TABLE .
                "
            WHERE batch >= ? ORDER BY batch DESC, migration DESC"
        );

        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function runMigration(string $path): void
    {
        $migration = $this->resolveMigration($path);
        $migration->up();

        $stmt = $this->connection->prepare(
            "INSERT INTO " .
                self::MIGRATIONS_TABLE .
                " (migration, batch)
            VALUES (?, ?)"
        );

        $stmt->execute([basename($path), $this->getNextBatchNumber()]);
    }

    private function rollbackMigration(string $migration): void
    {
        $this->resolveMigration($migration)->down();

        $stmt = $this->connection->prepare(
            "DELETE FROM " . self::MIGRATIONS_TABLE . " WHERE migration = ?"
        );

        $stmt->execute([basename($migration)]);
    }

    private function resolveMigration(string $path): Migration
    {
        require_once $path;
        $className = $this->getMigrationClassName($path);
        return new $className($this->connection);
    }

    private function getMigrationClassName(string $path): string
    {
        $filename = basename($path, ".php");
        return preg_replace("/^\d{4}_\d{2}_\d{2}_\d{6}_/", "", $filename);
    }

    private function getRanMigrationNames(): array
    {
        $stmt = $this->connection->query(
            "SELECT migration FROM " . self::MIGRATIONS_TABLE
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getNextBatchNumber(): int
    {
        $stmt = $this->connection->query(
            "SELECT MAX(batch) FROM " . self::MIGRATIONS_TABLE
        );
        return (int) $stmt->fetchColumn() + 1;
    }

    private function getLastBatch(): int
    {
        $stmt = $this->connection->query(
            "SELECT MAX(batch) FROM " . self::MIGRATIONS_TABLE
        );
        return (int) $stmt->fetchColumn();
    }
}
