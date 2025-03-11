<?php

declare(strict_types=1);

namespace Forge\Core;

use Forge\Core\Database\{Config, Connection};
use Forge\Core\Config\Environment;
use Forge\Core\Config\EnvParser;
use Forge\Core\DI\Container;
use Forge\Core\Http\Kernel;
use Forge\Core\Routing\{ControllerLoader, Router};

final class Bootstrap
{
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
        if (!is_dir(BASE_PATH . "/database")) {
            mkdir(BASE_PATH . "/database", 0755, true);
        }
    }

    public static function setupContainer(): Container
    {
        $env = Environment::getInstance();

        $container = Container::getInstance();

        $dbConfig = new Config(
            driver: $env->get("DB_DRIVER"),
            database: $env->get("DB_DRIVER") === "sqlite"
                ? BASE_PATH . $env->get("DB_NAME")
                : $env->get("DB_NAME"),
            host: $env->get("DB_HOST"),
            username: $env->get("DB_USER"),
            password: $env->get("DB_PASS"),
            port: $env->get("DB_PORT")
        );

        // Register connection as singleton
        $container->singleton(Connection::class, function () use ($dbConfig) {
            return new Connection($dbConfig);
        });

        // Register Migrator service
        $container->register(\Forge\Core\Database\Migrator::class);

        return $container;
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
}
