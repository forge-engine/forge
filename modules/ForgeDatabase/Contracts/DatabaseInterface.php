<?php

namespace Forge\Modules\ForgeDatabase\Contracts;

interface DatabaseInterface
{
    public function connect(array $config): void;

    public function query(string $sql, array $params = []): array;

    public function execute(string $sql, array $params = []): mixed;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollback(): void;

    public function lastInsertId(): string;
}