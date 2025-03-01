<?php

namespace Forge\Core\Helpers;

class Framework
{
    public static function version(): string
    {
        return '0.1.0';
    }

    public static function isVersionCompatible(string $currentVersion, string $requiredVersion): bool
    {
        $operator = '=';
        if (str_starts_with($requiredVersion, '>=')) {
            $operator = '>=';
            $requiredVersion = substr($requiredVersion, 2);
        } elseif (str_starts_with($requiredVersion, '<=')) {
            $operator = '<=';
            $requiredVersion = substr($requiredVersion, 2);
        } elseif (str_starts_with($requiredVersion, '=')) {
            $operator = '=';
            $requiredVersion = substr($requiredVersion, 1);
        }

        if (!preg_match('/^\d+\.\d+\.\d+$/', $requiredVersion) || !preg_match('/^\d+\.\d+\.\d+$/', $currentVersion)) {
            return version_compare($currentVersion, $requiredVersion, $operator);
        }

        return version_compare($currentVersion, $requiredVersion, $operator);
    }

    public static function isPhpVersionCompatible(string $currentVersion, string $requiredVersion): bool
    {
        return version_compare($currentVersion, $requiredVersion, '>=');

    }
}