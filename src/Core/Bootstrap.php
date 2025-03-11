<?php

declare(strict_types=1);

namespace Forge\Core;

use Forge\CLI\Commands\HelpCommand;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Database\{Config, Connection};
use Forge\Core\Config\Environment;
use Forge\Core\Config\EnvParser;
use Forge\Core\DI\Container;
use Forge\Core\Database\Migrator;
use Forge\Core\Http\Kernel;
use Forge\Core\Routing\{ControllerLoader, Router};
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

final class Bootstrap
{
    private const CLASS_MAP_CACHE_FILE =
        BASE_PATH . "/storage/framework/cache/class-map.php";

    /**
     * @throws \ReflectionException
     */
    public static function init(): Kernel
    {
        self::loadEnvironment();
        self::setupErrorHandling();

        self::setupDatabase();
        $container = self::setupContainer();
        $router = self::setupRouter($container);

        return new Kernel($router, $container);
    }

    private static function loadEnvironment(): void
    {
        $envPath = BASE_PATH . "/.env";

        if (file_exists($envPath)) {
            EnvParser::load($envPath);
        }
        Environment::getInstance();
    }

    private static function setupErrorHandling(): void
    {
        ini_set(
            "display_errors",
            Environment::getInstance()->isDevelopment() ? "1" : "0"
        );
        error_reporting(E_ALL);
    }

    public static function shouldCacheViews(): bool
    {
        return Environment::getInstance()->get("VIEW_CACHE") &&
            !Environment::getInstance()->isDevelopment();
    }

    private static function setupDatabase(): void
    {
        if (!is_dir(BASE_PATH . "/storage/database")) {
            mkdir(BASE_PATH . "/storage/database", 0755, true);
        }
    }

    public static function setupContainer(): Container
    {
        $env = Environment::getInstance();

        $container = Container::getInstance();

        self::initConnection($container, $env);
        self::autoDiscoverServices($container);

        return $container;
    }
    public static function setupCliContainer(): Container
    {
        $env = Environment::getInstance();
        $container = Container::getInstance();

        self::initConnection($container, $env);

        $container->singleton(Migrator::class, function () use ($container) {
            return new Migrator($container->get(Connection::class));
        });

        $container->singleton(HelpCommand::class, function () use ($container) {
            return new HelpCommand($container);
        });

        self::autoDiscoverServices($container);

        return $container;
    }

    private static function initConnection(
        Container $container,
        Environment $env
    ): void {
        $container->singleton(Config::class, function () use ($env) {
            $env->get("DB_DRIVER") . "\n";
            return new Config(
                driver: $env->get("DB_DRIVER"),
                database: $env->get("DB_DRIVER") === "sqlite"
                    ? BASE_PATH . $env->get("DB_NAME")
                    : $env->get("DB_NAME"),
                host: $env->get("DB_HOST"),
                username: $env->get("DB_USER"),
                password: $env->get("DB_PASS"),
                port: $env->get("DB_PORT")
            );
        });

        $container->singleton(Connection::class, function () use ($container) {
            $config = $container->get(Config::class);
            return new Connection($config);
        });

        $container->singleton(PDO::class, function () use ($container) {
            $connection = $container->get(Connection::class);
            return $connection->getPdo();
        });
    }

    private static function autoDiscoverServices(Container $container): void
    {
        $classMap = self::loadClassMapCache();

        if ($classMap) {
            // Use cached class map
            foreach ($classMap as $class => $filepath) {
                if (class_exists($class)) {
                    // Check if class still exists (optional, for robustness)
                    try {
                        $reflectionClass = new ReflectionClass($class);
                        if (
                            !empty(
                                $reflectionClass->getAttributes(Service::class)
                            )
                        ) {
                            $container->register($class);
                        }
                    } catch (\ReflectionException $e) {
                        // Handle reflection error if needed, maybe log it
                    }
                }
            }
            return; // Skip directory scanning if cache is used
        }

        $serviceDirectories = [
            BASE_PATH . "/app/Repositories",
            BASE_PATH . "/app/Services",
            BASE_PATH . "/src/Core/Database",
        ];

        $newClassMap = []; // To build a new class map if cache is not used

        foreach ($serviceDirectories as $directory) {
            if (is_dir($directory)) {
                $directoryIterator = new RecursiveDirectoryIterator($directory);
                $iterator = new RecursiveIteratorIterator($directoryIterator);

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === "php") {
                        $filepath = $file->getPathname();
                        $class = self::fileToClass($filepath, BASE_PATH);
                        if (class_exists($class)) {
                            try {
                                $reflectionClass = new ReflectionClass($class);
                                $serviceAttribute = $reflectionClass->getAttributes(
                                    Service::class
                                );

                                if (!empty($serviceAttribute)) {
                                    $container->register($class);
                                    $newClassMap[$class] = $filepath; // Add to new class map
                                }
                            } catch (\ReflectionException $e) {
                                $class . " - " . $e->getMessage() . "\n";
                            }
                        }
                    }
                }
            }
        }
        self::generateClassMapCache($newClassMap); // Generate cache after scanning
    }

    /**
     * Helper function to convert file path to class name
     */
    private static function fileToClass(
        string $filepath,
        string $basePath
    ): string {
        $relativePath = str_replace($basePath, "", $filepath);
        $class = str_replace([".php", "/"], ["", "\\"], $relativePath);

        $class = ltrim($class, "\\");
        if (str_starts_with($class, "src\\Core\\")) {
            $class = str_replace("src\\Core\\", "Forge\\Core\\", $class);
        } elseif (str_starts_with($class, "app\\")) {
            $class = str_replace("app\\", "App\\", $class);
        }
        return $class;
    }

    /**
     * @throws \ReflectionException
     */
    private static function setupRouter(Container $container): Router
    {
        $loader = new ControllerLoader(
            $container,
            BASE_PATH . "/app/Controllers"
        );
        $loader->registerControllers();

        $router = new Router($container);
        foreach ($container->getServiceIds() as $id) {
            if (str_starts_with($id, "App\Controllers")) {
                $router->registerControllers($id);
            }
        }

        return $router;
    }

    /**
     * Loads the class map from cache if it exists and is valid.
     * @return array<string, string>|null Class map array or null if cache is not found or invalid.
     */
    private static function loadClassMapCache(): ?array
    {
        if (file_exists(self::CLASS_MAP_CACHE_FILE)) {
            try {
                $cachedData = include self::CLASS_MAP_CACHE_FILE;
                if (is_array($cachedData)) {
                    return $cachedData;
                }
            } catch (\Exception $e) {
                // Cache file might be corrupted, ignore and regenerate
            }
        }
        return null; // No valid cache found
    }

    /**
     * Generates and caches the class map to a file.
     * @param array<string, string> $classMap
     */
    private static function generateClassMapCache(array $classMap): void
    {
        if (!is_dir(dirname(self::CLASS_MAP_CACHE_FILE))) {
            mkdir(dirname(self::CLASS_MAP_CACHE_FILE), 0777, true); // Create cache directory if it doesn't exist
        }

        $cacheContent = "<?php return " . var_export($classMap, true) . ";";
        file_put_contents(self::CLASS_MAP_CACHE_FILE, $cacheContent);
    }
}
