<?php

namespace Forge\Modules\ForgeDatabase\Adapters;

use Forge\Core\Contracts\Events\EventDispatcherInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Events\DatabaseQueryExecuted;
use Forge\Core\Helpers\Debug;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use PDO;
use PDOException;

class PostgresqlAdapter implements DatabaseInterface
{
    private ?PDO $pdo = null;
    private EventDispatcherInterface $eventDispatcher;
    private string $connectionName;

    public function __construct(Container $container, string $connectionName)
    {
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
        $this->connectionName = $connectionName;
    }

    public function connect(array $config): void
    {

        $dsn = "pgsql:host={$config['host']};";
        if (isset($config['port'])) {
            $dsn .= "port={$config['port']};";
        }
        $dsn .= "dbname={$config['database']};";
        if (isset($config['options']['sslmode'])) {
            $dsn .= "sslmode={$config['options']['sslmode']};";
        }

        if (isset($config['unix_socket'])) {
            $dsn .= "unix_socket={$config['unix_socket']};";
        } else {
            $dsn .= "host={$config['host']};";
            if (isset($config['port'])) {
                $dsn .= "port={$config['port']};";
            }
        }
        $dsn .= "dbname={$config['database']}";


        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

//        if (isset($config['ssl'])) {
//            if (isset($config['ssl']['ca'])) {
//                $options[PDO::PGSQL_ATTR_SSL_CA] = $config['ssl']['ca'];
//            }
//            if (isset($config['ssl']['cert'])) {
//                $options[PDO::PGSQL_ATTR_SSL_CERT] = $config['ssl']['cert'];
//            }
//            if (isset($config['ssl']['key'])) {
//                $options[PDO::PGSQL_ATTR_SSL_KEY] = $config['ssl']['key'];
//            }
//            if (isset($config['ssl']['verify_peer'])) {
//                $options[PDO::PGSQL_ATTR_SSL_VERIFY_PEER] = $config['ssl']['verify_peer'];
//            }
//            if (isset($config['ssl']['verify_peer_name'])) {
//                $options[PDO::PGSQL_ATTR_SSL_VERIFY_PEER_NAME] = $config['ssl']['verify_peer_name'];
//            }
//        }

        try {
            $this->pdo = new PDO(
                $dsn,
                $config['username'] ?? null,
                $config['password'] ?? null,
                $options
            );
            if (!empty($config['charset'])) {
                $this->pdo->exec("SET NAMES '" . $config['charset'] . "'");
            }
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage(), 0, $e);
        }
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
        $this->ensureConnected();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $endTime = microtime(true);
        $queryTime = ($endTime - $starTime) * 1000;

        $this->dispatchQueryEvent($sql, $params, $queryTime);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function execute(string $sql, array $params = []): int
    {
        $starTime = microtime(true);
        $this->ensureConnected();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $endTime = microtime(true);
        $queryTime = ($endTime - $starTime) * 1000;

        $this->dispatchQueryEvent($sql, $params, $queryTime);
        return $stmt->rowCount();
    }

    public function beginTransaction(): void
    {
        $this->ensureConnected();
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->ensureConnected();
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->ensureConnected();
        $this->pdo->rollBack();
    }

    public function lastInsertId(): string
    {
        $this->ensureConnected();
        return $this->pdo->lastInsertId();
    }

    private function ensureConnected(): void
    {
        if (!$this->pdo) {
            throw new \RuntimeException("Database connection not established. Call connect() first.");
        }
    }
}