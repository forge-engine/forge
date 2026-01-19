<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB\Migrations;

use App\Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\MetaData;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Relations\BelongsTo;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Relations\ManyToMany;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\SoftDelete;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Status;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Timestamps;
use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;
use App\Modules\ForgeDatabaseSQL\DB\Schema\FormatterInterface;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Helpers\FileExistenceCache;
use Forge\Core\Helpers\Strings;
use PDOException;
use ReflectionClass;

abstract class Migration
{
    protected array $schema = [];
    protected array $indexes = [];
    protected array $relationships = [];
    protected ?QueryBuilderInterface $queryBuilder = null;
    private array $columnOrder = [];

    public function __construct(
        protected DatabaseConnectionInterface $pdo,
        protected FormatterInterface $formatter,
    ) {
        if (class_exists(\App\Modules\ForgeSqlOrm\ORM\QueryBuilder::class)) {
            $this->queryBuilder = new \App\Modules\ForgeSqlOrm\ORM\QueryBuilder($this->pdo);
        }
        $this->formatter = $formatter;

        $reflector = new ReflectionClass($this);
        $tableAttributes = $reflector->getAttributes(Table::class);
        if (!empty($tableAttributes)) {
            $this->reflectSchema();
            $this->reflectRelationships();
        }
    }

    private function reflectSchema(): void
    {
        $reflector = new ReflectionClass($this);
        $this->schema["columns"] = [];
        $columnOrder = [];

        $tableAttributes = $reflector->getAttributes(Table::class);
        if (empty($tableAttributes)) {
            return;
        }
        $table = $tableAttributes[0]->newInstance();
        $this->schema["table"] = $table->name;

        foreach ($reflector->getProperties() as $property) {
            $columnAttributes = $property->getAttributes(Column::class);
            if (!empty($columnAttributes)) {
                $column = $columnAttributes[0]->newInstance();
                $columnType = $this->resolveColumnType($column->type);
                $columnName = $column->name;
                $this->schema["columns"][$columnName] = [
                    "type" => $columnType->value,
                    "primary" => $column->primaryKey,
                    "nullable" => $column->nullable,
                    "unique" => $column->unique,
                    "default" => $column->default,
                    "autoIncrement" => $column->autoIncrement ?? false,
                ];
                if (
                    $column->length !== null &&
                    $columnType === ColumnType::STRING
                ) {
                    $this->schema["columns"][$columnName]["length"] =
                        $column->length;
                }
                if (
                    $column->enum !== null &&
                    $columnType === ColumnType::ENUM
                ) {
                    $this->schema["columns"][$columnName]["enum"] =
                        $column->enum;
                }
                $columnOrder[] = $columnName;
            }
        }

        foreach ($reflector->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof Status) {
                $columnName = $instance->column;
                $this->schema["columns"][$columnName] = [
                    "type" => ColumnType::ENUM ->value,
                    "enum" => $instance->values,
                    "nullable" => $instance->nullable,
                    "default" => "PENDING",
                    "primary" => false,
                    "unique" => false,
                    "autoIncrement" => false,
                ];
                if (!in_array($columnName, $columnOrder)) {
                    $columnOrder[] = $columnName;
                }
            }
        }

        foreach ($reflector->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof MetaData) {
                $columnName = $instance->column;
                $this->schema["columns"][$columnName] = [
                    "type" => ColumnType::JSON->value,
                    "nullable" => true,
                    "default" => null,
                    "primary" => false,
                    "unique" => false,
                    "autoIncrement" => false,
                ];
                if (!in_array($columnName, $columnOrder)) {
                    $columnOrder[] = $columnName;
                }
            }
        }

        foreach ($reflector->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof Timestamps) {
                $createdAtColumn = $instance->createdAt;
                $updatedAtColumn = $instance->updatedAt;
                $this->schema["columns"][$createdAtColumn] = [
                    "type" => ColumnType::TIMESTAMP->value,
                    "nullable" => true,
                    "default" => "CURRENT_TIMESTAMP",
                    "primary" => false,
                    "unique" => false,
                    "autoIncrement" => false,
                ];
                $this->schema["columns"][$updatedAtColumn] = [
                    "type" => ColumnType::TIMESTAMP->value,
                    "nullable" => true,
                    "default" => "CURRENT_TIMESTAMP",
                    "primary" => false,
                    "unique" => false,
                    "autoIncrement" => false,
                ];
                if (!in_array($createdAtColumn, $columnOrder)) {
                    $columnOrder[] = $createdAtColumn;
                }
                if (!in_array($updatedAtColumn, $columnOrder)) {
                    $columnOrder[] = $updatedAtColumn;
                }
            }
        }

        foreach ($reflector->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof SoftDelete) {
                $columnName = $instance->column;
                $this->schema["columns"][$columnName] = [
                    "type" => ColumnType::TIMESTAMP->value,
                    "nullable" => true,
                    "default" => null,
                    "primary" => false,
                    "unique" => false,
                    "autoIncrement" => false,
                ];
                if (!in_array($columnName, $columnOrder)) {
                    $columnOrder[] = $columnName;
                }
            }
        }

        $this->columnOrder = $columnOrder;

        foreach ($reflector->getAttributes(Index::class) as $indexAttribute) {
            $index = $indexAttribute->newInstance();
            $this->indexes[] = [
                "name" => $index->name,
                "columns" => $index->columns,
                "unique" => $index->unique,
                "table" => $this->schema["table"],
            ];
        }

        $multiTenantFile =
            BASE_PATH . "/modules/ForgeMultiTenant/src/ForgeMultiTenantModule.php";

        $multitenantReady = FileExistenceCache::exists($multiTenantFile);
        if ($multitenantReady) {
            $tenantScoped = false;
            foreach ($reflector->getAttributes() as $attribute) {
                if (
                    $attribute->getName() ===
                    'App\\Modules\\ForgeMultiTenant\\Attributes\\TenantScoped'
                ) {
                    $tenantScoped = true;
                    break;
                }
            }


        }
    }

    private function reflectRelationships(): void
    {
        $reflector = new ReflectionClass($this);

        foreach ($reflector->getAttributes(BelongsTo::class) as $attr) {
            $relation = $attr->newInstance();
            $this->formatter->addRelationship("belongsTo", [
                "foreignKey" =>
                    $relation->foreignKey ?:
                    Strings::toSnakeCase($relation->related) . "_id",
                "relatedTable" => Strings::toPlural(
                    Strings::toSnakeCase($relation->related),
                ),
                "onDelete" => $relation->onDelete,
            ]);
        }

        foreach ($reflector->getAttributes(ManyToMany::class) as $attr) {
            $relation = $attr->newInstance();
            $this->formatter->addRelationship("manyToMany", [
                "joinTable" => $relation->joinTable,
                "foreignKey" => $relation->foreignKey,
                "relatedKey" => $relation->relatedKey,
                "sourceTable" => $this->schema["table"],
                "relatedTable" => Strings::toPlural(
                    Strings::toSnakeCase($relation->related),
                ),
            ]);
        }
    }

    public function up(): void
    {
        if (empty($this->schema) || !isset($this->schema["table"])) {
            return;
        }

        $columnDefinitions = [];
        foreach ($this->columnOrder as $columnName) {
            if (isset($this->schema["columns"][$columnName])) {
                $columnDefinitions[] = $this->formatter->formatColumn(
                    $columnName,
                    $this->schema["columns"][$columnName],
                );
            }
        }

        $columnsSql = implode(",\n", $columnDefinitions);

        $driver = $this->pdo->getDriver();
        $identifierQuote = $this->getIdentifierQuote($driver);
        $quotedTableName = $identifierQuote . $this->schema["table"] . $identifierQuote;
        $sql = "CREATE TABLE IF NOT EXISTS {$quotedTableName} (\n{$columnsSql}\n)";

        if (!empty($this->indexes)) {
            foreach ($this->indexes as $index) {
                $sql .= ";\n" . $this->formatter->formatIndex($index);
            }
        }

        $sql .= $this->formatter->formatTableOptions();
        $sql .=
            "\n" .
            $this->formatter->formatRelationships($this->schema["table"]);

        if ($this->pdo->getDriver() === "sqlite") {
            $sql = preg_replace(
                "/,\s*FOREIGN\s+KEY\s*\([^)]+\)\s*REFERENCES\s+[^)]+\)/i",
                "",
                $sql,
            );
            $this->formatter->skipForeignKeys = true;
        }

        $this->execute($sql);
    }

    protected function execute(string $sql): void
    {
        if (empty(trim($sql))) {
            throw new MigrationException(
                "Migration " . static::class . " attempted to execute empty SQL. " .
                "For raw migrations, ensure your SQL is properly built before calling execute(). " .
                "The QueryBuilder's createTable() and getSql() methods are not implemented - " .
                "build SQL strings manually instead.",
                $sql,
            );
        }

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new MigrationException(
                "Migration failed: " . $e->getMessage(),
                $sql,
            );
        }
    }

    public function down(): void
    {
        if (empty($this->schema) || !isset($this->schema["table"])) {
            return;
        }

        $sql = $this->dropTable($this->schema["table"]);
        $this->execute($sql);
    }

    /**
     * Create a table with the given columns
     * 
     * @param string $tableName The name of the table
     * @param array<string, string> $columns Array of column definitions (e.g., ['id' => 'INTEGER PRIMARY KEY AUTOINCREMENT'])
     * @param bool $ifNotExists Whether to add IF NOT EXISTS clause
     * @return string The generated SQL
     */
    public function createTable(string $tableName, array $columns, bool $ifNotExists = false): string
    {
        $driver = $this->pdo->getDriver();
        return $this->buildCreateTableSql($tableName, $columns, $ifNotExists, $driver);
    }

    /**
     * Drop a table
     * 
     * @param string $tableName The name of the table to drop
     * @return string The generated SQL
     */
    public function dropTable(string $tableName): string
    {
        $driver = $this->pdo->getDriver();
        return $this->buildDropTableSql($tableName, $driver);
    }

    /**
     * Create an index on a table
     * 
     * @param string $tableName The name of the table
     * @param string $indexName The name of the index
     * @param array<string> $columns Array of column names
     * @param bool $unique Whether the index is unique
     * @return string The generated SQL
     */
    public function createIndex(string $tableName, string $indexName, array $columns, bool $unique = false): string
    {
        $driver = $this->pdo->getDriver();
        $identifierQuote = $this->getIdentifierQuote($driver);
        $quotedColumns = array_map(fn($col) => $identifierQuote . $col . $identifierQuote, $columns);
        $quotedTableName = $identifierQuote . $tableName . $identifierQuote;
        $quotedIndexName = $identifierQuote . $indexName . $identifierQuote;

        $uniqueClause = $unique ? 'UNIQUE ' : '';

        return sprintf(
            'CREATE %sINDEX IF NOT EXISTS %s ON %s (%s)',
            $uniqueClause,
            $quotedIndexName,
            $quotedTableName,
            implode(', ', $quotedColumns)
        );
    }

    /**
     * Add a foreign key constraint
     * 
     * @param string $tableName The name of the table
     * @param string $foreignKey The foreign key column name
     * @param string $referencedTable The referenced table name
     * @param string $referencedColumn The referenced column name (default: 'id')
     * @param string $onDelete The ON DELETE action (default: 'CASCADE')
     * @return string|null The generated SQL, or null if SQLite (foreign keys skipped)
     */
    public function addForeignKey(string $tableName, string $foreignKey, string $referencedTable, string $referencedColumn = 'id', string $onDelete = 'CASCADE'): ?string
    {
        $driver = $this->pdo->getDriver();

        // SQLite foreign keys are handled differently - skip ALTER TABLE for SQLite
        if ($driver === 'sqlite') {
            return null;
        }

        $identifierQuote = $this->getIdentifierQuote($driver);

        $quotedTable = $identifierQuote . $tableName . $identifierQuote;
        $quotedForeignKey = $identifierQuote . $foreignKey . $identifierQuote;
        $quotedReferencedTable = $identifierQuote . $referencedTable . $identifierQuote;
        $quotedReferencedColumn = $identifierQuote . $referencedColumn . $identifierQuote;

        return sprintf(
            'ALTER TABLE %s ADD FOREIGN KEY (%s) REFERENCES %s(%s) ON DELETE %s',
            $quotedTable,
            $quotedForeignKey,
            $quotedReferencedTable,
            $quotedReferencedColumn,
            $onDelete
        );
    }

    /**
     * Build CREATE TABLE SQL based on database driver
     */
    private function buildCreateTableSql(string $tableName, array $columns, bool $ifNotExists, string $driver): string
    {
        $identifierQuote = $this->getIdentifierQuote($driver);
        $quotedTableName = $identifierQuote . $tableName . $identifierQuote;

        $columnDefinitions = [];
        foreach ($columns as $columnName => $columnDef) {
            $quotedColumnName = $identifierQuote . $columnName . $identifierQuote;
            $normalizedDef = $this->normalizeColumnDefinition($columnDef, $driver);
            $columnDefinitions[] = $quotedColumnName . ' ' . $normalizedDef;
        }

        $columnsSql = implode(",\n    ", $columnDefinitions);
        $ifNotExistsClause = $ifNotExists ? ' IF NOT EXISTS' : '';

        $sql = "CREATE TABLE{$ifNotExistsClause} {$quotedTableName} (\n    {$columnsSql}\n)";

        if ($driver === 'mysql') {
            $sql .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        }

        return $sql;
    }

    /**
     * Build DROP TABLE SQL based on database driver
     */
    private function buildDropTableSql(string $tableName, string $driver): string
    {
        $identifierQuote = $this->getIdentifierQuote($driver);
        $quotedTableName = $identifierQuote . $tableName . $identifierQuote;
        return "DROP TABLE {$quotedTableName}";
    }

    /**
     * Get identifier quote character based on driver
     */
    private function getIdentifierQuote(string $driver): string
    {
        return match ($driver) {
            'mysql' => '`',
            'sqlite', 'pgsql' => '"',
            default => '"',
        };
    }

    /**
     * Normalize column definition based on database driver
     */
    private function normalizeColumnDefinition(string $definition, string $driver): string
    {
        $definition = trim($definition);

        if ($driver === 'pgsql') {
            if (preg_match('/\bINTEGER\b/i', $definition) && preg_match('/\b(?:AUTO_INCREMENT|AUTOINCREMENT)\b/i', $definition)) {
                $definition = preg_replace('/\bINTEGER\b/i', 'SERIAL', $definition);
                $definition = preg_replace('/\s+(?:AUTO_INCREMENT|AUTOINCREMENT)\b/i', '', $definition);
            } elseif (preg_match('/\bBIGINT\b/i', $definition) && preg_match('/\b(?:AUTO_INCREMENT|AUTOINCREMENT)\b/i', $definition)) {
                $definition = preg_replace('/\bBIGINT\b/i', 'BIGSERIAL', $definition);
                $definition = preg_replace('/\s+(?:AUTO_INCREMENT|AUTOINCREMENT)\b/i', '', $definition);
            }
        } elseif ($driver === 'mysql') {
            $definition = preg_replace('/\bAUTOINCREMENT\b/i', 'AUTO_INCREMENT', $definition);
            if (preg_match('/\bINTEGER\b(?!\s+(?:UNSIGNED|ZEROFILL))/i', $definition)) {
                $definition = preg_replace('/\bINTEGER\b/i', 'INT', $definition);
            }
        } elseif ($driver === 'sqlite') {
            $definition = preg_replace('/\bAUTO_INCREMENT\b/i', 'AUTOINCREMENT', $definition);
        }

        return $definition;
    }

    /**
     * Resolve column type from ColumnType enum or string
     */
    private function resolveColumnType(
        string|ColumnType $type,
    ): ColumnType {
        if ($type instanceof ColumnType) {
            return $type;
        }
        // String value
        return ColumnType::from($type);
    }
}
