<?php

declare(strict_types=1);

namespace Forge\Core;

use Forge\Exceptions\ClassNotFoundException;

final class Autoloader
{
    private static array $paths = [
        "App" => BASE_PATH . "/app",
        "Forge" => BASE_PATH . "/engine",
        "App\Modules" => BASE_PATH . "/modules",
        "App\View\Components" => BASE_PATH . "/app/views/components",
        "App\View\Layouts" => BASE_PATH . "/apps/views/layouts",
    ];

    public static function register(): void
    {
        spl_autoload_register([self::class, "load"]);
    }

    public static function addPath(string $namespace, string $path): void
    {
        self::$paths[$namespace] = $path;
    }

    private static function load(string $className): void
    {
        $className = ltrim($className, "\\");
        $parts = explode("\\", $className);

        foreach (self::$paths as $prefix => $baseDir) {
            if (str_starts_with($className, $prefix)) {
                $relativeClass = substr($className, strlen($prefix));
                $parts = explode("\\", $relativeClass);
                $file =
                    $baseDir . implode(DIRECTORY_SEPARATOR, $parts) . ".php";

                if (file_exists($file)) {
                    require $file;
                    return;
                }
            }
        }

        throw new ClassNotFoundException($className);
    }
}
