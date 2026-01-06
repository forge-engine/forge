<?php
declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM;

use Forge\Core\Contracts\Database\QueryBuilderInterface;
use Forge\Core\DI\Container;
use LogicException;
use ReflectionException;

final class ModelQuery
{
    private QueryBuilderInterface $builder;
    private string $model;
    private array $withRelations = [];

    public function __construct(string $model)
    {
        if (!class_exists($model)) {
            throw new LogicException("Model class '{$model}' does not exist.");
        }

        if (!is_subclass_of($model, Model::class)) {
            throw new LogicException("{$model} must extend base Model.");
        }

        $this->model = $model;
        $this->builder = Container::getInstance()->get(QueryBuilderInterface::class)
            ->table($model::table());
    }

    /** @return array<Model> */
    public function get(): array
    {
        $results = array_map(
            $this->model::fromRow(...),
            $this->builder->get()
        );

        if (!empty($this->withRelations)) {
            $this->loadRelations($results);
        }

        return $results;
    }

    private function loadRelations(array $models): void
    {
        if (empty($models)) {
            return;
        }

        $loader = new RelationLoader(...$models);
        $loader->load(...$this->withRelations);
    }

    public function id(int|string $id): self
    {
        $pk = $this->model::primaryProperty()->getName();
        return $this->where($pk, '=', $id);
    }

    public function where(string $column, mixed $operator = '=', mixed $value = null): self
    {
        $this->builder = $this->builder->where($column, $operator, $value);
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->builder = $this->builder->whereNull($column);
        return $this;
    }

    /**
     * @throws ReflectionException
     */
    public function onlyTrashed(): self
    {
        $col = $this->model::softDeleteColumn();
        if ($col === null) {
            throw new LogicException('Model is not soft-deletable');
        }
        $this->builder = $this->builder->whereNotNull($col);
        return $this;
    }

    public function first(): ?Model
    {
        $row = $this->builder->first();
        if ($row === null) {
            return null;
        }

        $model = $this->model::fromRow($row);

        if (!empty($this->withRelations)) {
            (new RelationLoader($model))->load(...$this->withRelations);
        }

        return $model;
    }

    public function insert(array $data): int|false
    {
        return $this->builder->insert($data);
    }

    public function forceDelete(): int
    {
        return $this->builder->delete();
    }

    /**
     * @throws ReflectionException
     */
    public function softDelete(): int
    {
        $col = $this->model::softDeleteColumn()
            ?? throw new LogicException('Model is not soft-deletable');

        return $this->builder->update([$col => date('Y-m-d H:i:s')]);
    }

    public function update(array $data): int
    {
        return $this->builder->update($data);
    }

    public function whereIn(string $column, array $values): self
    {
        $this->builder = $this->builder->whereIn($column, $values);
        return $this;
    }

    public function with(string ...$relations): self
    {
        $this->withRelations = array_merge($this->withRelations, $relations);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->builder = $this->builder->orderBy($column, $direction);
        return $this;
    }
}