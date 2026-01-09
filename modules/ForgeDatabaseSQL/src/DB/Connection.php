<?php

declare(strict_types=1);

namespace App\Modules\ForgeDatabaseSQL\DB;

use Forge\Core\Contracts\Database\DatabaseConfigInterface;
use Forge\Core\Contracts\Database\DatabaseConnectionInterface;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class Connection implements DatabaseConnectionInterface
{
    private PDO $pdo;

    public function __construct(DatabaseConfigInterface $config)
    {
        $dsn = $config->getDsn();
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
                $config->getOptions(),
            );
        }

        try {
            $this->pdo = new PDO(
                $dsn,
                $config->username,
                $config->password,
                $pdoOptionsToUse,
            );

            if ($config->driver === "sqlite") {
                $this->pdo->exec("PRAGMA foreign_keys = ON;");
                $this->pdo->exec("PRAGMA busy_timeout = 2000;");
            }
        } catch (PDOException $exception) {
            throw new RuntimeException(
                "Database connection failed: " . $exception->getMessage(),
            );
        }
    }

    public function exec(string $statement): int|false
    {
        return $this->pdo->exec($statement);
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

    public function prepare(string $statement): PDOStatement
    {
        return $this->pdo->prepare($statement);
    }

    public function query(string $statement): PDOStatement
    {
        return $this->pdo->query($statement);
    }

    public function getDriver(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}
