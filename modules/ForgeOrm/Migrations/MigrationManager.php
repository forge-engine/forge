<?php

namespace Forge\Modules\ForgeOrm\Migrations;

use Forge\Core\Helpers\App;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeOrm\Schema\Blueprint;
use Forge\Modules\ForgeOrm\Schema\Schema;

class MigrationManager
{
    use OutputHelper;

    private const MIGRATION_TABLE = 'forge_migrations';

    private DatabaseInterface $db;
    private Schema $schema;

    public function __construct()
    {
        $databaseInstance = App::getContainer()->get(DatabaseInterface::class);
        $schema = App::getContainer()->get(Schema::class);
        $this->db = $databaseInstance;
        $this->schema = $schema;
        $this->ensureMigrationTableExists();
    }

    public function runMigrations(): void
    {
        $env = App::env('APP_ENV');
        if ($env === 'production') {
            $this->error('Migration cant run in production');
            exit(0);
        }
        try {
            $this->db->beginTransaction();
            $pendingMigrations = $this->getPendingMigrations();

            if (empty($pendingMigrations)) {
                $this->info('No pending migrations to run.');
                return;
            }

            $this->comment('Running migrations:');
            foreach ($pendingMigrations as $migrationName => $migrationDetails) {
                $this->runMigration($migrationDetails);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
        $this->db->beginTransaction();
        $this->info('Migrations completed.');
    }

    public function rollbackLastMigration(): void
    {
        $env = App::env('APP_ENV');
        if ($env === 'production') {
            $this->error('Migration cant run in production');
            exit(0);
        }

        $this->db->beginTransaction();

        try {
            $lastMigrationName = $this->getLastMigration();

            if ($lastMigrationName) {
                $allMigrations = $this->getAllMigrations();

                if (isset($allMigrations[$lastMigrationName])) {
                    $lastMigrationPath = $allMigrations[$lastMigrationName]['path'];
                    $migrationDetails = ['name' => $lastMigrationName, 'path' => $lastMigrationPath];
                    $this->rollbackMigration($migrationDetails);
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function ensureMigrationTableExists(): void
    {
        if (!$this->schema->hasTable(self::MIGRATION_TABLE)) {
            $this->schema->create(self::MIGRATION_TABLE, function (Blueprint $table) {
                $table->integer('id', true, true)->primary();
                $table->string('migration', 255);
                $table->integer('batch');
                $table->timestamps();
            });
        }
    }

    private function getPendingMigrations(): array
    {
        $ranMigrations = $this->getRanMigrationNames();
        $allMigrations = $this->getAllMigrations();

        $pendingMigrations = [];
        foreach ($allMigrations as $migrationName => $migrationInfo) {
            if (!in_array($migrationName, $ranMigrations)) {
                $pendingMigrations[$migrationName] = $migrationInfo;
            }
        }
        return $pendingMigrations;
    }

    private function getRanMigrationNames(): array
    {
        $results = $this->db->query(
            "SELECT migration FROM " . self::MIGRATION_TABLE
        );
        return array_column($results, 'migration');
    }

    private function getAllMigrations(): array
    {
        return array_merge(
            $this->discoverModuleMigrations(),
            $this->discoverAppMigrations()
        );
    }

    private function discoverModuleMigrations(): array
    {
        $migrations = [];
        $modulesPath = BASE_PATH . '/modules/';

        foreach (glob($modulesPath . '*/Database/Migrations/*.php') as $file) {
            $migrationName = $this->getMigrationNameFromFilePath($file);
            $namespace = $this->getMigrationNamespaceFromFilePath($file, 'Modules');
            $className = $this->getClassName($migrationName, $namespace);
            $migrations[$migrationName] = ['name' => $migrationName, 'path' => $file, 'class' => $className, 'namespace' => $namespace];
        }
        return $migrations;
    }

    private function discoverAppMigrations(): array
    {
        $migrations = [];
        $appsPath = BASE_PATH . '/apps/';

        foreach (glob($appsPath . '*/Database/Migration/*.php') as $file) {
            $migrationName = $this->getMigrationNameFromFilePath($file);
            $namespace = $this->getMigrationNamespaceFromFilePath($file, 'Apps');
            $className = $this->getClassName($migrationName, $namespace);
            $migrations[$migrationName] = ['name' => $migrationName, 'path' => $file, 'class' => $className, 'namespace' => $namespace];
        }
        return $migrations;
    }

    private function getMigrationNameFromFilePath(string $path): string
    {
        return basename($path, '.php');
    }

    private function getMigrationNamespaceFromFilePath(string $filePath, string $type): string
    {
        $segments = explode(DIRECTORY_SEPARATOR, $filePath);
        $moduleOrAppName = $segments[array_search('modules', $segments) + 1] ?? $segments[array_search('apps', $segments) + 1] ?? '';
        return "Forge\\{$type}\\{$moduleOrAppName}\\Database\\Migrations";
    }

    private function getClassName(string $filename, string $namespace): string
    {
        $className = preg_replace("/^\d+_/", "", $filename);
        $className = str_replace("_", "", ucwords($className, "_"));
        return "{$namespace}\\{$className}";
    }

    private function runMigration(array $migration): void
    {
        if (!file_exists($migration['path'])) {
            $this->error("Error: Migration file not found at path: " . $migration['path']);
            return;
        }

        $instance = new $migration['class']();
        $instance->up();
        $this->recordMigration($migration['name']);
    }

    private function rollbackMigration(array $migration): void
    {
        $className = $migration['name'];
        $instance = new $className();
        try {
            $instance->down($this->schema);
            $this->removeMigrationRecord($migration['name']);
        } catch (\Throwable $e) {
            $this->error("Error during migration 'down' for '{$migration['name']}': " . $e->getMessage());
        }
    }

    private function recordMigration(string $migration): void
    {
        $batch = $this->getNextBatchNumber();
        $createdAt = date('Y-m-d H:i:s');

        $insertResult = $this->db->table(self::MIGRATION_TABLE)->insert([
            'migration' => $migration,
            'batch' => $batch,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        if ($insertResult === false) {
            $this->error("Failed to record migration in database: " . $migration);
        }
    }

    private function removeMigrationRecord(string $migration): void
    {
        $deleteResult = $this->db->execute(
            "DELETE FROM " . self::MIGRATION_TABLE . " WHERE migration = ?",
            [$migration]
        );
        if ($deleteResult === false) {
            $this->error("Failed to remove migration record from database: " . $migration);
        }
    }

    private function getLastMigration(): ?string
    {
        $result = $this->db->query(
            "SELECT migration FROM " . self::MIGRATION_TABLE . " ORDER BY created_at DESC LIMIT 1"
        );

        if (empty($result)) {
            return null;
        }

        return $result[0]['migration'] ?? null;
    }

    private function getNextBatchNumber(): int
    {
        $result = $this->db->query(
            "SELECT MAX(batch) as max_batch FROM " . self::MIGRATION_TABLE
        );

        return ($result[0]['max_batch'] ?? 0) + 1;
    }
}
