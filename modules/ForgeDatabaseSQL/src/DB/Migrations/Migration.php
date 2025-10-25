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
use App\Modules\ForgeSqlOrm\ORM\QueryBuilder;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\Helpers\Strings;
use PDOException;
use ReflectionClass;

abstract class Migration
{
    protected array $schema = [];
    protected array $indexes = [];
    protected array $relationships = [];
    protected QueryBuilderInterface $queryBuilder;
    private array $columnOrder = [];

    public function __construct(
        protected DatabaseConnectionInterface $pdo,
        protected FormatterInterface          $formatter,
    )
    {
        $this->queryBuilder = new QueryBuilder($this->pdo);
        $this->formatter = $formatter;
        $this->reflectSchema();
        $this->reflectRelationships();
    }

    private function reflectSchema(): void
    {
        $reflector = new ReflectionClass($this);
        $this->schema['columns'] = [];
        $columnOrder = [];

        $tableAttributes = $reflector->getAttributes(Table::class);
        if (!empty($tableAttributes)) {
            $table = $tableAttributes[0]->newInstance();
            $this->schema['table'] = $table->name;
        }

        foreach ($reflector->getProperties() as $property) {
            $columnAttributes = $property->getAttributes(Column::class);
            if (!empty($columnAttributes)) {
                $column = $columnAttributes[0]->newInstance();
                $columnType = $column->type instanceof ColumnType ? $column->type : ColumnType::from($column->type);
                $columnName = $column->name;
                $this->schema['columns'][$columnName] = [
                    'type' => $columnType->value,
                    'primary' => $column->primaryKey,
                    'nullable' => $column->nullable,
                    'unique' => $column->unique,
                    'default' => $column->default,
                    'autoIncrement' => $column->autoIncrement ?? false,
                ];
                if ($column->length !== null && $columnType === ColumnType::STRING) {
                    $this->schema['columns'][$columnName]['length'] = $column->length;
                }
                if ($column->enum !== null && $columnType === ColumnType::ENUM) {
                    $this->schema['columns'][$columnName]['enum'] = $column->enum;
                }
                $columnOrder[] = $columnName;
            }
        }

        foreach ($reflector->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof Status) {
                $columnName = $instance->column;
                $this->schema['columns'][$columnName] = [
                    'type' => ColumnType::ENUM->value,
                    'enum' => $instance->values,
                    'nullable' => $instance->nullable,
                    'default' => 'PENDING',
                    'primary' => false,
                    'unique' => false,
                    'autoIncrement' => false,
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
                $this->schema['columns'][$columnName] = [
                    'type' => ColumnType::JSON->value,
                    'nullable' => true,
                    'default' => null,
                    'primary' => false,
                    'unique' => false,
                    'autoIncrement' => false,
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
                $this->schema['columns'][$createdAtColumn] = [
                    'type' => ColumnType::TIMESTAMP->value,
                    'nullable' => true,
                    'default' => 'CURRENT_TIMESTAMP',
                    'primary' => false,
                    'unique' => false,
                    'autoIncrement' => false,
                ];
                $this->schema['columns'][$updatedAtColumn] = [
                    'type' => ColumnType::TIMESTAMP->value,
                    'nullable' => true,
                    'default' => 'CURRENT_TIMESTAMP',
                    'primary' => false,
                    'unique' => false,
                    'autoIncrement' => false,
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
                $this->schema['columns'][$columnName] = [
                    'type' => ColumnType::TIMESTAMP->value,
                    'nullable' => true,
                    'default' => null,
                    'primary' => false,
                    'unique' => false,
                    'autoIncrement' => false,
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
                'name' => $index->name,
                'columns' => $index->columns,
                'unique' => $index->unique,
                'table' => $this->schema['table'],
            ];
        }

        $multiTenantFile = BASE_PATH . '/modules/ForgeDebugBar/src/ForgeMultiTenantModule.php';
        $multitenantReady = is_file($multiTenantFile)
            && class_exists(\App\Modules\ForgeMultiTenant\ForgeMultiTenantModule::class);
        if ($multitenantReady) {
            if (
                !empty($reflector->getAttributes(\App\Modules\ForgeMultiTenant\Attributes\TenantScoped::class)) &&
                class_exists(\App\Modules\ForgeMultiTenant\ForgeMultiTenantModule::class)
            ) {
                $this->schema['columns']['tenant_id'] = [
                    'type' => ColumnType::STRING->value,
                    'length' => 36,
                    'nullable' => false,
                    'default' => null,
                    'primary' => false,
                    'unique' => false,
                    'autoIncrement' => false,
                ];
                if (!in_array('tenant_id', $this->columnOrder)) {
                    $this->columnOrder[] = 'tenant_id';
                }

                if ($this->pdo->getDriver() !== 'sqlite') {
                    $this->indexes[] = [
                        'name' => 'idx_' . $this->schema['table'] . '_tenant_id',
                        'columns' => ['tenant_id'],
                        'unique' => false,
                        'table' => $this->schema['table'],
                    ];
                }
            }
        }


    }

    private function reflectRelationships(): void
    {
        $reflector = new ReflectionClass($this);

        foreach ($reflector->getAttributes(BelongsTo::class) as $attr) {
            $relation = $attr->newInstance();
            $this->formatter->addRelationship('belongsTo', [
                'foreignKey' => $relation->foreignKey ?: Strings::toSnakeCase($relation->related) . '_id',
                'relatedTable' => Strings::toPlural(Strings::toSnakeCase($relation->related)),
                'onDelete' => $relation->onDelete
            ]);
        }

        foreach ($reflector->getAttributes(ManyToMany::class) as $attr) {
            $relation = $attr->newInstance();
            $this->formatter->addRelationship('manyToMany', [
                'joinTable' => $relation->joinTable,
                'foreignKey' => $relation->foreignKey,
                'relatedKey' => $relation->relatedKey,
                'sourceTable' => $this->schema['table'],
                'relatedTable' => Strings::toPlural(Strings::toSnakeCase($relation->related))
            ]);
        }
    }

    public function up(): void
    {
        if (empty($this->schema)) {
            return;
        }

        $columnDefinitions = [];
        foreach ($this->columnOrder as $columnName) {
            if (isset($this->schema['columns'][$columnName])) {
                $columnDefinitions[] = $this->formatter->formatColumn($columnName, $this->schema['columns'][$columnName]);
            }
        }

        $columnsSql = implode(",\n", $columnDefinitions);

        $sql = "CREATE TABLE `{$this->schema['table']}` (\n{$columnsSql}\n)";

        if (!empty($this->indexes)) {
            foreach ($this->indexes as $index) {
                $sql .= ";\n" . $this->formatter->formatIndex($index);
            }
        }

        $sql .= $this->formatter->formatTableOptions();
        $sql .= "\n" . $this->formatter->formatRelationships($this->schema['table']);

        if ($this->pdo->getDriver() === 'sqlite') {
            $sql = preg_replace('/,\s*FOREIGN\s+KEY\s*\([^)]+\)\s*REFERENCES\s+[^)]+\)/i', '', $sql);
            $this->formatter->skipForeignKeys = true;
        }

        $this->queryBuilder->execute($sql);
    }

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

    public function down(): void
    {
        if (empty($this->schema)) {
            return;
        }

        $sql = $this->queryBuilder
            ->setTable($this->schema['table'])
            ->dropTable($this->schema['table']);

        $this->queryBuilder->execute($sql);
    }
}
