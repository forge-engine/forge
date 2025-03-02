<?php

namespace Forge\Modules\ForgeDatabase\Contracts;

use Forge\Modules\ForgeOrm\QueryBuilder;

interface DatabaseInterface
{
    public function connect(array $config): void;

    public function query(string $sql, array $params = []): array;

    public function execute(string $sql, array $params = []): mixed;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollback(): void;

    public function lastInsertId(): string;

    /**
     * Get a query builder instance for fluent database operations.
     *
     * @param string $table The name of the table to start building a query for.
     * @return QueryBuilder
     */
    public function table(string $table): QueryBuilder;
}