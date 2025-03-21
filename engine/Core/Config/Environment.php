<?php

declare(strict_types=1);

namespace Forge\Core\Config;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class Environment
{
    private static ?self $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->config = [
            'APP_ENV' => $_ENV['APP_ENV'] ?? 'production',
            'APP_DEBUG' => (bool)($_ENV['APP_DEBUG'] ?? false),
            'DB_DRIVER' => $_ENV['DB_DRIVER'] ?? 'sqlite',
            'DB_HOST' => $_ENV['DB_HOST'] ?? 'localhost',
            'DB_PORT' => (int)($_ENV['DB_PORT'] ?? 0),
            'DB_NAME' => $_ENV['DB_NAME'] ?? '',
            'DB_USER' => $_ENV['DB_USER'] ?? '',
            'DB_PASS' => $_ENV['DB_PASS'] ?? '',
            'VIEW_CACHE' => (bool)($_ENV['VIEW_CACHE'] ?? true)
        ];
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }

    public function isDevelopment(): bool
    {
        return $this->get('APP_ENV') === 'development';
    }
}
