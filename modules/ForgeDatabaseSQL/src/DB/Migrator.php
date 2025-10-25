<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB;

use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;
use App\Modules\ForgeDatabaseSQL\DB\Schema\MySqlFormatter;
use App\Modules\ForgeDatabaseSQL\DB\Schema\SqliteFormatter;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Traits\StringHelper;
use PDO;
use ReflectionException;
use RuntimeException;
use ReflectionClass;
use Throwable;

final class Migrator
{
    use StringHelper;

    private const string MIGRATIONS_TABLE = "forge_migrations";
    private const string CORE_MIGRATIONS_PATH = BASE_PATH . "/engine/Database/Migrations";
    private const string APP_MIGRATIONS_PATH = BASE_PATH . "/app/Database/migrations";
    private const string MODULES_PATH = BASE_PATH . "/modules";
    private ?int $currentBatch = null;

    public function __construct(private DatabaseConnectionInterface $connection)
    {
        $this->ensureMigrationsTable();
    }

    /**
     * Ensures the migration table exists with necessary metadata columns.
     */
    private function ensureMigrationsTable(): void
    {
        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS " .
            self::MIGRATIONS_TABLE .
            " (
                migration VARCHAR(255) PRIMARY KEY,
                batch INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                module VARCHAR(255) NULL,
                migration_group VARCHAR(255) NULL
            )"
        );
    }

    public function createMigrationTable(): void
    {
        $this->ensureMigrationsTable();
    }

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Retrieves the list of migrations that are pending to be run based on filters (for preview).
     *
     * @param string|null $scope Defaults to 'all'. The type of migrations to preview: 'all', 'app', 'core', or 'module'.
     * @throws ReflectionException
     */
    public function previewRun(?string $scope = 'all', ?string $module = null, ?string $group = null): array
    {
        return $this->getPendingMigrations($scope, $module, $group);
    }

    /**
     * Discovers pending migrations based on the given scope and filters.
     *
     * @param string|null $scope
     * @param string|null $module
     * @param string|null $group
     * @return array<string> List of full file paths.
     * @throws ReflectionException
     */
    private function getPendingMigrations(?string $scope, ?string $module, ?string $group): array
    {
        $ran = $this->getRanMigrationNames();
        $scope = $scope ?? 'all';
        $module = $module ? $this->toPascalCase($module) : null;

        $moduleForDiscovery = ($scope === 'module' && $module !== null) ? $module : null;

        $allFiles = $this->discoverMigrationFiles($scope, $moduleForDiscovery);

        $pendingFiles = [];

        foreach ($allFiles as $path) {
            if (in_array(basename($path), $ran)) {
                continue;
            }

            [, $migrationType, $migrationModule, $migrationGroup] = $this->extractMigrationMetadata($path);

            if ($module !== null) {
                if ($migrationType !== 'module' || $migrationModule !== $this->toPascalCase($module)) {
                    continue;
                }
            }

            if ($group !== null && $migrationGroup !== $group) {
                continue;
            }

            $pendingFiles[] = $path;
        }

        return $pendingFiles;
    }

    private function getRanMigrationNames(): array
    {
        $stmt = $this->connection->query(
            "SELECT migration FROM " . self::MIGRATIONS_TABLE
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @return array<string> List of full file paths, sorted alphabetically/chronologically.
     */
    private function discoverMigrationFiles(string $scope, ?string $module): array
    {
        $paths = [];

        switch ($scope) {
            case 'core':
                $paths[] = self::CORE_MIGRATIONS_PATH;
                break;
            case 'app':
                $paths[] = self::APP_MIGRATIONS_PATH;
                break;
            case 'module':
                $paths = $this->getModuleMigrationPaths($module);
                break;
            case 'all':
                $paths[] = self::CORE_MIGRATIONS_PATH;
                $paths[] = self::APP_MIGRATIONS_PATH;
                $paths = array_merge($paths, $this->getModuleMigrationPaths(null));
                break;
            default:
                break;
        }

        $files = [];
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $files = array_merge($files, glob($path . '/*.php'));
            }
        }

        sort($files);
        return $files;
    }

    /**
     * Returns an array of directory paths for module migrations.
     * @param string|null $target Target module name, or null for all modules.
     * @return array<string> List of full directory paths
     */
    private function getModuleMigrationPaths(?string $target = null): array
    {
        $paths = [];
        if (!is_dir(self::MODULES_PATH)) {
            return [];
        }

        $modules = $target ? [$target] : array_filter(scandir(self::MODULES_PATH), function ($item) {
            return is_dir(self::MODULES_PATH . '/' . $item) && !in_array($item, ['.', '..']);
        });

        foreach ($modules as $moduleName) {
            $central = self::MODULES_PATH . '/' . $moduleName . '/src/Database/Migrations';
            if (is_dir($central)) {
                $paths[] = $central;
            }

            $tenant = self::MODULES_PATH . '/' . $moduleName . '/src/Database/Migrations/Tenants';
            if (is_dir($tenant)) {
                $paths[] = $tenant;
            }
        }

        return $paths;
    }

    /**
     * Uses reflection and path analysis to determine migration metadata.
     *
     * @param string $path Full path to the migration file.
     * @return array{0: string, 1: string, 2: ?string, 3: ?string} [ClassName, Type, Module, Group]
     * @throws ReflectionException
     */
    private function extractMigrationMetadata(string $path): array
    {
        require_once $path;
        $className = $this->getMigrationClassName($path);
        $reflection = new ReflectionClass($className);

        $group = null;
        $type = 'app';
        $module = null;

        $attributes = $reflection->getAttributes(GroupMigration::class);
        if (!empty($attributes)) {
            $instance = $attributes[0]->newInstance();
            $group = $instance->name ?? null;
        }

        $relativePath = str_replace(BASE_PATH . '/', '', $path);

        if (str_starts_with($relativePath, 'engine/Database/Migrations')) {
            $type = 'core';
        } elseif (str_starts_with($relativePath, 'app/Database/Migrations')) {
            $type = 'app';
        } elseif (str_starts_with($relativePath, 'modules/')) {
            $type = 'module';
            if (preg_match('/^modules\/([^\/]+)\//', $relativePath, $matches)) {
                $module = $matches[1];
            }
        }

        return [$className, $type, $module, $group];
    }

    private function getMigrationClassName(string $path): string
    {
        $filename = basename($path, ".php");
        return preg_replace("/^\d{4}_\d{2}_\d{2}_\d{6}_/", "", $filename);
    }

    public function previewRollback(int $steps = 1): array
    {
        return $this->getRanMigrations($steps);
    }

    /**
     * Retrieves ran migrations based on complex filters for rollback.
     *
     * @param int $steps
     * @param string|null $type
     * @param string|null $module
     * @param string|null $group
     * @param int|null $batch
     * @return array<string> List of migration filenames.
     */
    public function getRanMigrations(int $steps, ?string $type = null, ?string $module = null, ?string $group = null, ?int $batch = null): array
    {
        $module = $module ? $this->toPascalCase($module) : null;

        $sql = "SELECT migration FROM " . self::MIGRATIONS_TABLE . " WHERE 1=1";
        $params = [];

        if ($batch === null) {
            $lastBatch = $this->getLastBatch();
            $minBatch = $lastBatch - $steps + 1;

            $sql .= " AND batch >= ?";
            $params[] = $minBatch;
        } else {
            $sql .= " AND batch = ?";
            $params[] = $batch;
        }

        if ($type !== null && strtolower($type) !== 'all') {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        if ($module !== null) {
            $sql .= " AND module = ?";
            $params[] = $module;
        }

        if ($group !== null) {
            $sql .= " AND migration_group = ?";
            $params[] = $group;
        }

        $sql .= " ORDER BY batch DESC, migration DESC";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getLastBatch(): int
    {
        $stmt = $this->connection->query(
            "SELECT MAX(batch) FROM " . self::MIGRATIONS_TABLE
        );
        return (int)$stmt->fetchColumn();
    }

    /**
     * Runs pending migrations based on scope and group filters.
     *
     * @param string|null $scope Defaults to 'all'. The type of migrations to run: 'all', 'app', 'core', or 'module'.
     * @param string|null $module The specific module name if scope is 'module'.
     * @param string|null $group The group name to filter migrations by.
     * @throws ReflectionException
     * @throws Throwable
     */
    public function run(?string $scope = 'all', ?string $module = null, ?string $group = null): void
    {
        $this->currentBatch = $this->getNextBatchNumber();
        $this->connection->beginTransaction();
        try {
            foreach ($this->getPendingMigrations($scope, $module, $group) as $migrationPath) {
                $this->runMigration($migrationPath);
            }
            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        } finally {
            $this->currentBatch = null;
        }
    }

    private function getNextBatchNumber(): int
    {
        $stmt = $this->connection->query(
            "SELECT MAX(batch) FROM " . self::MIGRATIONS_TABLE
        );
        return (int)$stmt->fetchColumn() + 1;
    }

    /**
     * Runs the migration and records its metadata in the database.
     * @throws ReflectionException
     */
    private function runMigration(string $path): void
    {
        if ($this->currentBatch === null) {
            throw new RuntimeException("Migration batch number not set.");
        }

        [, $type, $module, $group] = $this->extractMigrationMetadata($path);

        $migration = $this->resolveMigration($path);
        $migration->up();

        $stmt = $this->connection->prepare(
            "INSERT INTO " .
            self::MIGRATIONS_TABLE .
            " (migration, batch, type, module, migration_group)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            basename($path),
            $this->currentBatch,
            $type,
            $module,
            $group,
        ]);
    }

    /**
     * @throws ReflectionException
     */
    private function resolveMigration(string $path): object|string
    {
        require_once $path;
        $className = $this->getMigrationClassName($path);
        $reflection = new ReflectionClass($className);

        $driver = $this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

        $formatter = match ($driver) {
            'mysql' => new MySqlFormatter(),
            'sqlite' => new SqliteFormatter(),
            default => throw new RuntimeException("Unsupported Database driver: $driver")
        };

        if (!$reflection->isSubclassOf(Migration::class)) {
            throw new RuntimeException("Invalid migration class: $className");
        }

        return $reflection->newInstance($this->connection, $formatter);
    }

    private function getPdo(): PDO
    {
        return $this->connection->getPdo();
    }

    /**
     * Rollback migrations based on complex filters.
     *
     * @param int $steps Number of batches to roll back (default 1). Ignored if $batch is set.
     * @param string|null $type Filter by type ('all', 'app', 'core', 'module').
     * @param string|null $module Filter by specific module name.
     * @param string|null $group Filter by migration group name.
     * @param int|null $batch Filter by specific batch number.
     * @throws Throwable
     */
    public function rollback(int $steps = 1, ?string $type = null, ?string $module = null, ?string $group = null, ?int $batch = null): void
    {
        $this->connection->beginTransaction();
        try {
            $migrations = $this->getRanMigrations($steps, $type, $module, $group, $batch);

            foreach ($migrations as $migration) {
                $this->rollbackMigration($migration);
            }

            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @throws ReflectionException
     */
    private function rollbackMigration(string $migration): void
    {
        $path = $this->findMigrationPath($migration);

        if (!$path) {
            throw new RuntimeException("Migration file not found for rollback: $migration");
        }

        $this->resolveMigration($path)->down();

        $stmt = $this->connection->prepare(
            "DELETE FROM " . self::MIGRATIONS_TABLE . " WHERE migration = ?"
        );

        $stmt->execute([basename($migration)]);
    }

    private function findMigrationPath(string $filename): ?string
    {
        $paths = $this->discoverMigrationFiles('all', null);

        foreach ($paths as $path) {
            if (basename($path) === $filename) {
                return $path;
            }
        }

        return null;
    }
}