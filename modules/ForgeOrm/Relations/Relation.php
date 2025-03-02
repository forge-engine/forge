<?php

namespace Forge\Modules\ForgeOrm\Relations;

use Forge\Core\Models\Model;
use Forge\Modules\ForgeOrm\Collection;
use Forge\Modules\ForgeOrm\QueryBuilder;

abstract class Relation
{
    /**
     * @var QueryBuilder
     */
    protected QueryBuilder $query;

    /**
     * @var Model
     */
    protected Model $parentModel;

    /**
     * @var string
     */
    protected string $relatedModelClass;

    /**
     * Constructor for Relation.
     *
     * @param QueryBuilder $query
     * @param Model $parentModel
     * @param string $relatedModelClass
     */
    public function __construct(QueryBuilder $query, Model $parentModel, string $relatedModelClass)
    {
        $this->query = $query;
        $this->parentModel = $parentModel;
        $this->relatedModelClass = $relatedModelClass;
    }

    /**
     * Get the results of the relationship. Must be implemented by subclasses.
     *
     * @return Collection
     */
    abstract public function getResults(): Collection;

    /**
     * Get the underlying QueryBuilder for the relationship.
     *
     * @return QueryBuilder
     */
    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    /**
     * Add a where clause to the relationship query.
     *
     * @param string $column
     * @param string|null $operator
     * @param mixed|null $value
     * @return $this
     */
    public function where(string $column, ?string $operator = null, mixed $value = null): self
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    /**
     * Add an order by clause to the relationship query.
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Set the limit for the relationship query.
     *
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * Set the offset for the relationship query.
     *
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->query->offset($offset);
        return $this;
    }

    /**
     * Get the first result of the relationship.
     *
     * @return Model|null
     */
    public function first(): ?Model
    {
        return $this->getResults()->first();
    }

    /**
     * Execute the query and get the Collection of results.
     *
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->getResults();
    }

    /**
     * Ensure the results of the relationship query are unique based on specified columns.
     *
     * @param string|array<string> $columns The column(s) to enforce uniqueness on.
     * @return $this
     */
    public function unique($columns): self
    {
        if (is_array($columns)) {
            $uniqueColumns = [];
            foreach ($columns as $column) {
                $uniqueColumns[] = "{$column}";
            }
            $uniqueKey = implode(', ', $uniqueColumns);
        } else {
            // Single column specified
            $uniqueKey = $columns;
        }
        $this->query->select([$uniqueKey])->distinct();

        return $this;
    }

    /**
     * Dynamically handle method calls (passthrough to QueryBuilder).
     *
     * @param string $method
     * @param array<int, mixed> $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        // Passthrough unknown method calls to the QueryBuilder
        if (method_exists($this->query, $method)) {
            return call_user_func_array([$this->query, $method], $parameters);
        }

        throw new \BadMethodCallException("Call to undefined method " . __CLASS__ . "::{$method}()");
    }
}