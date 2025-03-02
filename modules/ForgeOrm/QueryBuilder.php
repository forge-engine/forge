<?php

namespace Forge\Modules\ForgeOrm;

use Forge\Core\Helpers\App;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeOrm\Relations\Relation;

class QueryBuilder
{
    /**
     * @var DatabaseInterface
     */
    protected DatabaseInterface $database;

    /**
     * @var string
     */
    protected string $table;

    /**
     * @var array<string>
     */
    protected array $selectColumns = ['*'];

    /**
     * @var array<array>
     */
    protected array $whereConditions = [];

    /**
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * @var int|null
     */
    protected ?int $offset = null;

    /**
     * @var array<string>
     */
    protected array $orderBy = [];

    /**
     * @var array<mixed>
     */
    protected array $bindings = [];

    /**
     * @var array<string> Relations to eager load.
     */
    protected array $eagerLoadRelations = [];

    /**
     * @var array<array>
     */
    protected array $joins = [];

    /**
     * @var bool
     */
    protected bool $distinct = false;


    public function __construct()
    {
        $container = App::getContainer();
        $database = $container->get(DatabaseInterface::class);
        $this->database = $database;
    }

    /**
     * Set the table to query.
     *
     * @param string $table
     * @return $this
     */
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the columns to select.
     *
     * @param array<string>|string $columns
     * @return $this
     */
    public function select(array|string $columns = ['*']): self
    {
        $this->selectColumns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add a where clause to the query.
     *
     * @param string $column
     * @param string|null $operator
     * @param mixed|null $value
     * @return $this
     */
    public function where(string $column, ?string $operator = null, mixed $value = null): self
    {
        if (is_null($value)) {
            $value = $operator;
            $operator = '=';
        }

        $this->whereConditions[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Set the limit for the query.
     *
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the offset for the query.
     *
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Set the query to select distinct rows.
     *
     * @return $this
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Add an order by clause to the query.
     *
     * @param string $column
     * @param string $direction 'asc' or 'desc' (default: 'asc')
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderBy[] = "{$column} " . strtoupper($direction);
        return $this;
    }


    public function with($relations): self
    {
        $this->eagerLoadRelations = $relations;
        return $this;
    }

    /**
     * Execute the query and return a Collection of results.
     *
     * @return Collection
     */
    public function get(): Collection
    {
        $sql = $this->toSql();
        $results = $this->database->query($sql, $this->bindings);
        return new Collection($results);
    }

    /**
     * Execute the query and return the first result as a Model (or null).
     *
     * @return mixed|null
     */
    public function first(): mixed
    {
        $this->limit(1);
        return $this->get()->first();
    }

    /**
     * Execute a raw SQL query and return a Collection of results.
     *
     * @param string $sql
     * @param array $params
     * @return Collection
     */
    public function raw(string $sql, array $params = []): Collection
    {
        return new Collection($this->database->query($sql, $params));
    }

    /**
     * Insert new records into the table.
     *
     * @param array<string, mixed> $values Associative array of column => value pairs.
     * @return string|int|bool Last insert ID or false on failure.
     */
    public function insert(array $values): string|int|bool
    {
        $columns = implode(', ', array_keys($values));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        return $this->database->execute($sql, array_values($values)); // execute() should return lastInsertId or false
    }

    /**
     * Update records in the table matching the where conditions.
     *
     * @param array<string, mixed> $values Associative array of column => value pairs for update.
     * @return int Number of affected rows.
     */
    public function update(array $values): int
    {
        $setClauses = [];
        foreach ($values as $column => $value) {
            $setClauses[] = "{$column} = ?";
            $this->bindings[] = $value; // Add update values to bindings
        }
        $setClauseSql = implode(', ', $setClauses);

        $sql = "UPDATE {$this->table} SET {$setClauseSql}";

        if (!empty($this->whereConditions)) {
            $whereClauses = [];
            foreach ($this->whereConditions as $condition) {
                $whereClauses[] = "{$condition['column']} {$condition['operator']} ?";
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        return $this->database->execute($sql, $this->bindings); // execute() should return affected rows
    }

    /**
     * Delete records from the table matching the where conditions.
     *
     * @return int Number of affected rows.
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->whereConditions)) {
            $whereClauses = [];
            foreach ($this->whereConditions as $condition) {
                $whereClauses[] = "{$condition['column']} {$condition['operator']} ?";
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        return $this->database->execute($sql, $this->bindings);
    }

    /**
     * Execute a count query and return the number of results.
     *
     * @return int
     */
    public function count(): int
    {
        $originalSelectColumns = $this->selectColumns;
        $this->selectColumns = ['COUNT(*) as aggregate'];
        $sql = $this->toSql();
        $result = $this->database->query($sql, $this->bindings);
        $this->selectColumns = $originalSelectColumns;

        if ($result && isset($result[0]['aggregate'])) {
            return (int)$result[0]['aggregate'];
        }

        return 0;
    }

    /**
     * Add a join clause to the query.
     *
     * @param string $table The name of the table to join.
     * @param string $first The first column to join on.
     * @param string $second The second column to join on.
     * @param string $operator The operator to use for the join condition (e.g., '=').
     * @param string $type The type of join (e.g., 'INNER', 'LEFT').
     * @return $this
     */
    public function join(string $table, string $first, string $second, string $operator = '=', string $type = 'INNER'): self
    {
        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * Compile the query to SQL.
     *
     * @return string
     */
    protected function toSql(): string
    {
        $sql = "SELECT " . ($this->distinct ? 'DISTINCT ' : '') . implode(', ', $this->selectColumns) . " FROM " . $this->table;

        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $sql .= " " . $join['type'] . " JOIN " . $join['table'] . " ON " . $join['first'] . " " . $join['operator'] . " " . $join['second'];
            }
        }

        if (!empty($this->whereConditions)) {
            $whereClauses = [];
            foreach ($this->whereConditions as $condition) {
                $whereClauses[] = "{$condition['column']} {$condition['operator']} ?";
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if (!is_null($this->limit)) {
            $sql .= " LIMIT " . $this->limit;
        }

        if (!is_null($this->offset)) {
            $sql .= " OFFSET " . $this->offset;
        }

        return $sql;
    }

    /**
     * Eager load the specified relations for a collection of models.
     *
     * @param Collection $collection
     * @param array<string> $relations
     * @return Collection
     */
    protected function eagerLoad(Collection $collection, array $relations): Collection
    {
        if ($collection->isEmpty()) {
            return $collection;
        }

        $modelClassName = get_class($collection->first());

        foreach ($relations as $relationName) {
            if (method_exists($modelClassName, $relationName)) {
                $modelInstance = new $modelClassName();
                $relation = $modelInstance->{$relationName}();

                if ($relation instanceof Relation) {
                    $collection = $this->performEagerLoad($collection, $relationName, $relation);
                }
            }
        }
        return $collection;
    }

    /**
     * Perform eager load for a single relationship.
     *
     * @param Collection $collection
     * @param string $relationName
     * @param Relation $relation
     * @return Collection
     */
    protected function performEagerLoad(Collection $collection, string $relationName, Relation $relation): Collection
    {
        $parentKeys = $collection->pluck($relation->parentModel->getPrimaryKey())->unique()->toArray();

        $relatedModels = $relation->getQuery()
            ->whereIn($relation->foreignKey, $parentKeys)
            ->get()
            ->keyBy($relation->foreignKey);

        foreach ($collection as $model) {
            $foreignKeyValue = $model->getAttribute($relation->parentModel->getPrimaryKey());
            if (isset($relatedModels[$foreignKeyValue])) {
                $model->setRelation($relationName, new Collection([$relatedModels[$foreignKeyValue]]));
            } else {
                $model->setRelation($relationName, new Collection([]));
            }
        }

        return $collection;
    }
}