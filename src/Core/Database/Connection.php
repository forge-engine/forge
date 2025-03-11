<?php

declare(strict_types=1);

namespace Forge\Core\Database;

use Forge\Core\DI\Attributes\Service;
use PDO;
use PDOException;

#[Service]
final class Connection
{
    private static ?PDO $instance = null;
    private PDO $pdo;

    public function __construct(Config $config)
    {
        $this->pdo = self::get($config);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function prepare(string $statement): \PDOStatement
    {
        return $this->pdo->prepare($statement);
    }

    public function query(string $statement): \PDOStatement
    {
        return $this->pdo->query($statement);
    }

    public function exec(string $statement): int|false
    {
        return $this->pdo->exec($statement);
    }

    public static function get(Config $config): PDO
    {
        if (self::$instance === null) {
            $dsn = $config->getDsn();

            try {
                $pdoOptionsToUse = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];

                if ($config->driver === "mysql") {
                    $pdoOptionsToUse = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ];
                } else {
                    $pdoOptionsToUse = array_merge(
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ],
                        $config->getOptions()
                    );
                }

                self::$instance = new PDO(
                    $dsn,
                    $config->username,
                    $config->password,
                    $pdoOptionsToUse
                );
            } catch (PDOException $exception) {
                throw new \RuntimeException(
                    "Database connection failed: " . $exception->getMessage()
                );
            }
        }

        return self::$instance;
    }
}
