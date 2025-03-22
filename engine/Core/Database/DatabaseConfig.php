<?php

declare(strict_types=1);

namespace Forge\Core\Database;

final class DatabaseConfig
{
    private array $driverOptions = [
        "sqlite" => [
            "dsn" => "sqlite:%database%",
            "options" => [],
        ],
        "mysql" => [
            "dsn" =>
                "mysql:host=%host%;port=%port%;dbname=%database%;charset=%charset%",
            "options" => [
                \PDO::ATTR_PERSISTENT => true,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '%charset%'",
            ],
        ],
        "pgsql" => [
            "dsn" => "pgsql:host=%host%;port=%port%;dbname=%database%",
            "options" => [
                \PDO::ATTR_PERSISTENT => true,
            ],
        ],
    ];

    public function __construct(
        public string $driver,
        public string $database,
        public string $host = "localhost",
        public string $username = "",
        public string $password = "",
        public int $port = 3306,
        public string $charset = "utf8mb4"
    ) {
        $this->validateDriver();
    }

    public function getDsn(): string
    {
        return str_replace(
            ["%host%", "%port%", "%database%", "%charset%"],
            [$this->host, $this->port, $this->database, $this->charset],
            $this->driverOptions[$this->driver]["dsn"]
        );
    }

    public function getOptions(): array
    {
        return $this->driverOptions[$this->driver]["options"];
    }

    private function validateDriver(): void
    {
        if (!array_key_exists($this->driver, $this->driverOptions)) {
            throw new \InvalidArgumentException(
                "Unsupported database driver: {$this->driver}"
            );
        }
    }
}
