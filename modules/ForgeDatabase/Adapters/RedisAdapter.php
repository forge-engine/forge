<?php

namespace Forge\Modules\ForgeDatabase\Adapters;

use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeOrm\QueryBuilder;
use Redis;
use RedisException;
use RuntimeException;

class RedisAdapter implements DatabaseInterface
{
    private ?Redis $redis = null;
    private bool $inTransaction = false;
    private ?int $lastInsertId = null;

    public function connect(array $config): void
    {
        $this->redis = new Redis();

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 6379;
        $timeout = $config['timeout'] ?? 0.0;
        $auth = $config['auth'] ?? null;
        $database = $config['database'] ?? null;

        try {
            if (!$this->redis->connect($host, $port, $timeout)) {
                throw new RuntimeException("Failed to connect to Redis server.");
            }

            if ($auth !== null) {
                if (!$this->redis->auth($auth)) {
                    throw new RuntimeException("Redis authentication failed.");
                }
            }

            if ($database !== null) {
                if (!$this->redis->select($database)) {
                    throw new RuntimeException("Failed to select Redis database.");
                }
            }
        } catch (RedisException $e) {
            throw new RuntimeException("Redis connection error: " . $e->getMessage(), 0, $e);
        }
    }

    public function query(string $sql, array $params = []): array
    {
        $this->ensureConnected();
        $result = $this->executeCommand($sql, $params);

        if (is_array($result)) {
            return $result;
        }

        return [$result];
    }

    public function execute(string $sql, array $params = []): int
    {
        $this->ensureConnected();
        $command = strtoupper($sql);
        $result = $this->executeCommand($sql, $params);

        if ($command === 'INCR' || $command === 'INCRBY') {
            $this->lastInsertId = is_numeric($result) ? (int)$result : null;
        }

        if ($result === true) {
            return 1;
        } elseif ($result === false) {
            return 0;
        }

        return (int)$result;
    }

    public function beginTransaction(): void
    {
        $this->ensureConnected();
        if ($this->inTransaction) {
            throw new RuntimeException("A transaction is already active.");
        }

        $this->executeCommand('MULTI', []);
        $this->inTransaction = true;
    }

    public function commit(): void
    {
        $this->ensureConnected();
        if (!$this->inTransaction) {
            throw new RuntimeException("No active transaction to commit.");
        }

        $this->executeCommand('EXEC', []);
        $this->inTransaction = false;
    }

    public function rollback(): void
    {
        $this->ensureConnected();
        if (!$this->inTransaction) {
            throw new RuntimeException("No active transaction to rollback.");
        }

        $this->executeCommand('DISCARD', []);
        $this->inTransaction = false;
    }

    public function lastInsertId(): string
    {
        return (string)$this->lastInsertId;
    }

    private function ensureConnected(): void
    {
        if ($this->redis === null || !$this->redis->isConnected()) {
            throw new RuntimeException("Redis connection not established. Call connect() first.");
        }
    }

    private function executeCommand(string $command, array $params): mixed
    {
        $this->ensureConnected();

        try {
            return $this->redis->rawCommand($command, ...$params);
        } catch (RedisException $e) {
            throw new RuntimeException("Redis command failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function table(string $table): QueryBuilder
    {
        return (new QueryBuilder())->table($table);
    }
}