<?php

namespace Forge\Modules\ForgeOrm\Schema;

use Forge\Core\Helpers\App;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgeOrm\QueryBuilder;

use Closure;


class Schema
{
    use OutputHelper;

    /**
     * @var QueryBuilder
     */
    protected QueryBuilder $queryBuilder;

    public function __construct()
    {
        $container = App::getContainer();
        $queryBuilder = $container->get(QueryBuilder::class);
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Create a new table on the schema.
     *
     * @param string $table
     * @param Closure $callback Blueprint definition callback.
     * @return bool True on success, false on failure.
     */
    public function create(string $table, Closure $callback): bool
    {
        $blueprint = new Blueprint();
        $callback($blueprint);

        $columns = $blueprint->getColumns();
        $foreignKeys = $blueprint->getForeignKeys();

        if (empty($columns)) {
            return false;
        }

        $columnDefinitions = implode(', ', array_merge($columns, $foreignKeys));
        $driver = $this->getDatabaseDriver();

        try {
            switch ($driver) {
                case 'sqlite':
                case 'mysql':
                case 'pgsql':
                    $sql = "CREATE TABLE {$table} ({$columnDefinitions})";
                    $this->queryBuilder->raw($sql);
                    return true;

                case 'redis':
                    return false;

                default:
                    throw new \RuntimeException("Unsupported database driver: {$driver}");
            }
        } catch (\Exception $e) {
            $this->error("Error creating table '{$table}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Drop a table from the schema.
     *
     * @param string $table
     * @return bool True on success, false on failure.
     */
    public function drop(string $table): bool
    {
        $driver = $this->getDatabaseDriver();

        try {
            switch ($driver) {
                case 'sqlite':
                case 'mysql':
                case 'pgsql':
                    $sql = "DROP TABLE IF EXISTS {$table}";
                    $this->queryBuilder->raw($sql);
                    return true;

                case 'redis':
                    return false;

                default:
                    throw new \RuntimeException("Unsupported database driver: {$driver}");
            }
        } catch (\Exception $e) {
            error_log("Error dropping table '{$table}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a table exists in the schema.
     *
     * @param string $table
     * @return bool True if the table exists, false otherwise.
     */
    public function hasTable(string $table): bool
    {
        $driver = $this->getDatabaseDriver();

        try {
            switch ($driver) {
                case 'sqlite':
                    $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name = :tableName";
                    $result = $this->queryBuilder->raw($sql, [':tableName' => $table]);
                    return !empty($result);

                case 'mysql':
                    $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tableName";
                    $result = $this->queryBuilder->raw($sql, [':tableName' => $table]);
                    return $result->isEmpty() === false;

                case 'pgsql':
                    $sql = "SELECT to_regclass('public." . $table . "')";
                    $result = $this->queryBuilder->raw($sql);
                    return $result[0]['to_regclass'] !== null;

                case 'redis':
                    return false;

                default:
                    throw new \RuntimeException("Unsupported database driver: {$driver}");
            }
        } catch (\Exception $e) {
            $this->error("Error checking table existence '{$table}' for driver '{$driver}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the database driver type.
     *
     * @return string The database driver type (e.g., 'mysql', 'sqlite', 'postgresql').
     */
    private function getDatabaseDriver(): string
    {
        $driverName = App::env('FORGE_DB_CONNECTION');
        return $driverName;
    }
}