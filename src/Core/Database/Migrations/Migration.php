<?php

declare(strict_types=1);

namespace Forge\Core\Database\Migrations;

use Forge\Core\Database\Config;
use Forge\Core\Database\Connection;
use PDO;
use PDOException;

abstract class Migration
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::get(
            new Config(
                driver: $_ENV['DB_DRIVER'] ?? 'sqlite',
                database: $_ENV['DB_NAME'] ?? BASE_PATH . '/database/database.sqlite',
                host: $_ENV['DB_HOST'] ?? 'localhost',
                username: $_ENV['DB_USER'] ?? '',
                password: $_ENV['DB_PASS'] ?? '',
                port: (int)($_ENV['DB_PORT'] ?? 0),
                charset: $_ENV['DB_CHARSET'] ?? 'utf8mb4'
            )
        );
    }

    abstract public function up(): void;
    abstract public function down(): void;

    protected function execute(string $sql): void
    {
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new MigrationException(
                "Migration failed: " . $e->getMessage(),
                $sql
            );
        }
    }
}
