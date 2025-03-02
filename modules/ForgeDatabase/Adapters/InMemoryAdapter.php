<?php

namespace Forge\Modules\ForgeDatabase\Adapters;

use Forge\Core\Contracts\Events\EventDispatcherInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Events\DatabaseQueryExecuted;
use Forge\Core\Helpers\Debug;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeOrm\QueryBuilder;

class InMemoryAdapter implements DatabaseInterface
{
    private \PDO $pdo;

    private EventDispatcherInterface $eventDispatcher;
    private string $connectionName;

    public function __construct(Container $container, string $connectionName)
    {
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
        $this->connectionName = $connectionName;
    }

    public function connect(array $config): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    private function dispatchQueryEvent(string $sql, array $params, float|int $queryTime): void
    {
        $origin = Debug::backtraceOrigin();
        $event = new DatabaseQueryExecuted($sql, $params, $queryTime, $this->connectionName, $origin);
        $this->eventDispatcher->dispatch($event);
    }

    public function query(string $sql, array $params = []): array
    {
        $starTime = microtime(true);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $endTime = microtime(true);
        $queryTime = ($endTime - $starTime) * 1000;

        $this->dispatchQueryEvent($sql, $params, $queryTime);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function execute(string $sql, array $params = []): int
    {
        $starTime = microtime(true);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $endTime = microtime(true);
        $queryTime = ($endTime - $starTime) * 1000;

        $this->dispatchQueryEvent($sql, $params, $queryTime);
        return $stmt->rowCount();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * @inheritDoc
     */
    public function table(string $table): QueryBuilder
    {
        return (new QueryBuilder())->table($table);
    }
}