<?php

namespace Forge\Core\Bootstrap;

class Autoloader
{
    private static bool $registered = false;
    private static array $psr4Mappings = [];
    private static array $fallbackDirs = [];
    private static array $classMap = [];

    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, '/') . '/';
        self::$psr4Mappings[$prefix] = $baseDir;
    }

    public static function addFallbackDir(string $dir): void
    {
        self::$fallbackDirs[] = rtrim($dir, '/') . '/';
    }

    public static function register(bool $prepend = false): void
    {
        if (!self::$registered) {
            spl_autoload_register([self::class, 'load'], true, $prepend);
            self::$registered = true;
        }
    }

    public static function load(string $class): void
    {
        if (isset(self::$classMap[$class])) {
            require self::$classMap[$class];
            return;
        }

        $file = self::findFile($class);

        if ($file) {
            self::$classMap[$class] = $file;
            require $file;
        }
    }

    private static function findFile(string $class): ?string
    {
        $prefix = $class;
        while (($pos = strrpos($prefix, '\\')) !== false) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);

            if (isset(self::$psr4Mappings[$prefix])) {
                $file = self::$psr4Mappings[$prefix]
                    . str_replace('\\', '/', $relativeClass)
                    . '.php';

                if (file_exists($file)) {
                    return $file;
                }
            }

            $prefix = rtrim($prefix, '\\');
        }

        foreach (self::$fallbackDirs as $dir) {
            $file = $dir . str_replace('\\', '/', $class) . '.php';
            if (file_exists($file)) {
                return $file;
            }
        }

        throw new \RuntimeException(
            "Class '{$class}' not found in PSR-4 mappings or fallback directories."
        );
    }

    public static function compileClassMap(string $outputFile): void
    {
        $classMap = [];

        foreach (self::$psr4Mappings as $prefix => $dir) {
            $dirIterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($dirIterator);

            foreach ($iterator as $file) {
                if ($file->getExtension() === 'php') {
                    $relativePath = str_replace($dir, '', $file->getPathname());
                    $class = $prefix . str_replace('/', '\\', substr($relativePath, 0, -4));
                    $classMap[$class] = $file->getPathname();
                }
            }
        }

        file_put_contents(
            $outputFile,
            '<?php return ' . var_export($classMap, true) . ';'
        );
    }

    public static function initialize(string $basePath): void
    {
        if (!self::$registered) {
            self::addNamespace('Forge', $basePath . '/engine');
            self::addNamespace('Forge\Modules', $basePath . '/modules');
            self::addNamespace('Tests', $basePath . '/tests');
            self::register();
        }
    }
}
