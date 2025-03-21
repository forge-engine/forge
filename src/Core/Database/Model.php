<?php
declare(strict_types=1);

namespace Forge\Core\Database;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Container;
use Forge\Exceptions\MissingPrimaryKeyException;
use Forge\Exceptions\MissingTableAttributeException;
use ReflectionClass;
use ReflectionProperty;

#[Service]
abstract class Model
{
    protected static string $connection = "default";
    private array $original = [];
    protected static array $with = [];
    protected static array $scopes = [];
    protected bool $softDelete = false;
    protected string $deletedAtColumn = "deleted_at";

    /**
     * Properties that should be hidden from object dumps
     */
    protected array $hidden = [];

    private static function getConnection(): \PDO
    {
        return Container::getInstance()->get(Connection::class);
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return Container::getInstance()->get(QueryBuilder::class);
    }

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
                $this->original[$key] = $value;
            }
        }
    }

    /**
     * Convert the model to an array, hiding protected properties
     */
    public function toArray(): array
    {
        $array = [];
        $reflection = new ReflectionClass($this);

        foreach (
            $reflection->getProperties(ReflectionProperty::IS_PUBLIC)
            as $property
        ) {
            $name = $property->getName();
            if (!in_array($name, $this->hidden) && !$property->isStatic()) {
                $array[$name] = $this->$name;
            }
        }

        return $array;
    }

    /**
     * Convert the model to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Handle JSON serialization
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function save(): bool
    {
        $data = $this->getDirtyAttributes();

        if ($this->exists()) {
            return $this->performUpdate($data);
        }

        return $this->performInsert($data);
    }

    public function delete(): bool
    {
        $primaryKey = $this->getPrimaryKey();
        $value = $this->$primaryKey ?? null;

        if ($value === null) {
            return false;
        }

        if ($this->softDelete) {
            return $this->getQueryBuilder()
                ->setTable($this->getTable())
                ->where($primaryKey, "=", $value)
                ->update([$this->deletedAtColumn => date("Y-m-d H:i:s")]) > 0;
        }

        return $this->getQueryBuilder()
            ->setTable($this->getTable())
            ->where($primaryKey, "=", $value)
            ->delete() > 0;
    }

    public static function find(int $id): ?static
    {
        $instance = new static();

        $query = $instance
            ->getQueryBuilder()
            ->setTable(static::getTable())
            ->where(static::getPrimaryKey(), "=", $id);

        if ($instance->softDelete) {
            $query->whereNull($instance->deletedAtColumn);
        }

        $model = $query->first(static::class);

        if ($model && !empty(static::$with)) {
            $model->loadRelations(static::$with);
        }

        return $model;
    }

    public static function all(): array
    {
        $instance = new static(); 

        $query = $instance
            ->getQueryBuilder()
            ->setTable(static::getTable()); 

        if ($instance->softDelete) {
            $query->whereNull($instance->deletedAtColumn);
        }

        $models = $query->get(static::class);

        if (!empty(static::$with) && !empty($models)) {
            foreach ($models as $model) {
                $model->loadRelations(static::$with);
            }
        }

        return $models;
    }

    protected function getDirtyAttributes(): array
    {
        $dirty = [];
        foreach ($this->getColumns() as $column) {
            if ($this->$column !== $this->original[$column]) {
                $dirty[$column] = $this->$column;
            }
        }
        return $dirty;
    }

    protected function exists(): bool
    {
        $primaryKey = $this->getPrimaryKey();
        return isset($this->$primaryKey);
    }

    public static function getTable(): string
    {
        $reflection = new ReflectionClass(static::class);
        $attributes = $reflection->getAttributes(Table::class);

        if (empty($attributes)) {
            throw new MissingTableAttributeException();
        }

        return $attributes[0]->newInstance()->name;
    }

    public static function getPrimaryKey(): string
    {
        $reflection = new ReflectionClass(static::class);

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);

            foreach ($attributes as $attribute) {
                $column = $attribute->newInstance();
                if ($column->primary) {
                    return $property->getName();
                }
            }
        }

        throw new MissingPrimaryKeyException();
    }

    private function getColumns(): array
    {
        $columns = [];
        $reflection = new ReflectionClass(static::class);

        foreach ($reflection->getProperties() as $property) {
            if ($property->getAttributes(Column::class)) {
                $columns[] = $property->getName();
            }
        }

        return $columns;
    }

    private function performInsert(array $data): bool
    {
        $result = $this->getQueryBuilder()
            ->setTable(static::getTable())
            ->insert($data);

        if ($result) {
            $primaryKey = $this->getPrimaryKey();
            $this->$primaryKey = $result;
        }

        return (bool) $result;
    }

    private function performUpdate(array $data): bool
    {
        $primaryKey = $this->getPrimaryKey();
        $value = $this->$primaryKey ?? null;

        if ($value === null) {
            return false;
        }

        return $this->getQueryBuilder()
            ->setTable(static::getTable()) 
            ->where($primaryKey, "=", $value)
            ->update($data) > 0;
    }

    /**
     * Define a one-to-many relationship
     */
    protected function hasMany(
        string $relatedClass,
        ?string $foreignKey = null,
        ?string $localKey = null
    ): array {
        $localKey = $localKey ?? $this->getPrimaryKey();
        $foreignKey =
            $foreignKey ??
            strtolower((new \ReflectionClass($this))->getShortName()) . "_id";

        $localKeyValue = $this->$localKey;

        return $this->getQueryBuilder()
            ->setTable($relatedClass::getTable())
            ->where($foreignKey, "=", $localKeyValue)
            ->get($relatedClass);
    }

    /**
     * Define a many-to-one relationship
     */
    protected function belongsTo(
        string $relatedClass,
        ?string $foreignKey = null,
        ?string $ownerKey = null
    ): ?object {
        $foreignKey =
            $foreignKey ??
            strtolower((new \ReflectionClass($relatedClass))->getShortName()) .
                "_id";
        $ownerKey = $ownerKey ?? (new $relatedClass())->getPrimaryKey();

        $foreignKeyValue = $this->$foreignKey;

        if ($foreignKeyValue === null) {
            return null;
        }

        return $this->getQueryBuilder()
            ->setTable($relatedClass::getTable())
            ->where($ownerKey, "=", $foreignKeyValue)
            ->first($relatedClass);
    }

    /**
     * Define a many-to-many relationship
     */
    protected function belongsToMany(
        string $relatedClass,
        ?string $pivotTable = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null
    ): array {
        $thisClass = static::class;

        if ($pivotTable === null) {
            $segments = [
                strtolower((new \ReflectionClass($thisClass))->getShortName()),
                strtolower(
                    (new \ReflectionClass($relatedClass))->getShortName()
                ),
            ];
            sort($segments);
            $pivotTable = implode("_", $segments);
        }

        $foreignPivotKey =
            $foreignPivotKey ??
            strtolower((new \ReflectionClass($thisClass))->getShortName()) .
                "_id";
        $relatedPivotKey =
            $relatedPivotKey ??
            strtolower((new \ReflectionClass($relatedClass))->getShortName()) .
                "_id";

        $localKey = $this->getPrimaryKey();
        $localKeyValue = $this->$localKey;

        $query = $this->getQueryBuilder()
            ->setTable($relatedClass::getTable());
        $query->select($relatedClass::getTable() . ".*");
        $query->join(
            $pivotTable,
            $pivotTable . "." . $relatedPivotKey,
            "=",
            $relatedClass::getTable() .
                "." .
                (new $relatedClass())->getPrimaryKey()
        );
        $query->where(
            $pivotTable . "." . $foreignPivotKey,
            "=",
            $localKeyValue
        );

        return $query->get($relatedClass);
    }

    /**
     * Load the given relationships
     */
    protected function loadRelations(array $relations): void
    {
        foreach ($relations as $relation) {
            if (method_exists($this, $relation)) {
                $this->$relation = $this->$relation();
            }
        }
    }

    /**
     * Set the relationships that should be eager loaded
     */
    public static function with(array $relations): QueryBuilder
    {
        static::$with = $relations;

        $instance = new static();

        return $instance
            ->getQueryBuilder()
            ->setTable(static::getTable());
    }

    /**
     * Apply a scope to the query
     */
    public static function scope(string $scope, ...$parameters): QueryBuilder
    {
        $instance = new static();

        $query = $instance
            ->getQueryBuilder()
            ->setTable(static::getTable());

        $scopeMethod = "scope" . ucfirst($scope);

        if (method_exists(static::class, $scopeMethod)) {
            $instance->$scopeMethod($query, ...$parameters);
        }

        return $query;
    }

    /**
     * Force delete a soft deleted model
     */
    public function forceDelete(): bool
    {
        $primaryKey = $this->getPrimaryKey();
        $value = $this->$primaryKey ?? null;

        if ($value === null) {
            return false;
        }

        return $this->getQueryBuilder()
            ->setTable(static::getTable())
            ->where($primaryKey, "=", $value)
            ->delete() > 0;
    }

    /**
     * Restore a soft deleted model
     */
    public function restore(): bool
    {
        if (!$this->softDelete) {
            return false;
        }

        $primaryKey = $this->getPrimaryKey();
        $value = $this->$primaryKey ?? null;

        if ($value === null) {
            return false;
        }

        return $this->getQueryBuilder()
            ->setTable(static::getTable())
            ->where($primaryKey, "=", $value)
            ->update([$this->deletedAtColumn => null]) > 0;
    }

    /**
     * Customize debug info to respect hidden properties
     */
    public function __debugInfo(): array
    {
        $debugInfo = [];
        $reflection = new ReflectionClass($this);

        foreach (
            $reflection->getProperties(ReflectionProperty::IS_PUBLIC)
            as $property
        ) {
            $name = $property->getName();
            if (!in_array($name, $this->hidden) && !$property->isStatic()) {
                $debugInfo[$name] = $this->$name;
            }
        }

        foreach (
            $reflection->getProperties(
                ReflectionProperty::IS_PROTECTED |
                    ReflectionProperty::IS_PRIVATE
            )
            as $property
        ) {
            $name = $property->getName();
            if (
                $name === "hidden" ||
                $name === "original" ||
                $name === "softDelete" ||
                $name === "deletedAtColumn"
            ) {
                $property->setAccessible(true);
                $debugInfo[$name] = $property->getValue($this);
            }
        }

        return $debugInfo;
    }
}
