<?php

declare(strict_types=1);

namespace Forge\Core\Config;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class EnvParser
{
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('.env file not found');
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = self::parseValue($value);
            }
        }
    }

    private static function parseValue(string $value): mixed
    {
        $value = trim($value, "'\" \t\n\r\0\x0B");

        if (strtolower($value) === 'true') return true;
        if (strtolower($value) === 'false') return false;
        if (is_numeric($value)) return (strpos($value, '.') !== false) ? (float)$value : (int)$value;

        return $value;
    }
}
