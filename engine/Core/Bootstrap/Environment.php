<?php

namespace Forge\Core\Bootstrap;

class Environment
{
    private string $baseDir;
    private array $env = [];

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
        $this->load();
    }

    private function load(): void
    {
        $envFile = $this->baseDir . '/.env';
        if (!file_exists($envFile)) return;

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;

            list($key, $value) = $this->parseLine($line);
            if ($key === null) continue;

            $_ENV[$key] = $_SERVER[$key] = $value;
            $this->env[$key] = $value;
        }
    }

    private function parseLine(string $line): ?array
    {
        if (strpos($line, '=') === false) return null;

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove quotes
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
            $value = $matches[1];
        }

        return [$key, $value];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $this->env[$key] ?? $default;
    }

    public function isLocal(): bool
    {
        return $this->get('APP_ENV', 'production') === 'local';
    }

    public function isDevelopment(): bool
    {
        return $this->get('APP_ENV', 'production') === 'development';
    }

    public function isProduction(): bool
    {
        return $this->get('APP_ENV', 'production') === 'production';
    }
}
