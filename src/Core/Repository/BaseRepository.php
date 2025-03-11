<?php

declare(strict_types=1);

namespace Forge\Core\Repository;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Database\Model;
use Forge\Core\Database\QueryBuilder;

#[Service]
abstract class BaseRepository
{
    protected QueryBuilder $queryBuilder;
    protected string $modelClass;
    protected ?string $dtoClass = null;

    /**
     * @param string $modelClass
     * @param string|null $dtoClass DTO class to hydrate results into (optional)
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        string $modelClass,
        ?string $dtoClass = null
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->modelClass = $modelClass;
        $this->dtoClass = $dtoClass;
        $this->queryBuilder->setTable($modelClass::getTable());
    }

    /**
     * @template T of object
     * @return array<T>|array<Model>
     */
    public function findAll(): array
    {
        if ($this->dtoClass !== null) {
            return $this->queryBuilder->get($this->dtoClass);
        }
        return $this->queryBuilder->get($this->modelClass);
    }

    /**
     * @template T of object
     * @return T|Model|null
     */
    public function findById(int $id): object|null
    {
        if ($this->dtoClass !== null) {
            return $this->queryBuilder
                ->where($this->getModelPrimaryKey(), "=", $id)
                ->first($this->dtoClass);
        }
        return $this->queryBuilder
            ->where($this->getModelPrimaryKey(), "=", $id)
            ->first($this->modelClass);
    }

    public function create(array $data): int|false
    {
        return $this->queryBuilder->insert($data);
    }

    public function update(int $id, array $data): int
    {
        return $this->queryBuilder
            ->where($this->getModelPrimaryKey(), "=", $id)
            ->update($data);
    }

    public function delete(int $id): int
    {
        return $this->queryBuilder
            ->where($this->getModelPrimaryKey(), "=", $id)
            ->delete();
    }

    protected function getModelPrimaryKey(): string
    {
        return $this->modelClass::getPrimaryKey();
    }
}
