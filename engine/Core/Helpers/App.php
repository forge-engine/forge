<?php

namespace Forge\Core\Helpers;

use Forge\Console\ConsoleKernel;
use Forge\Core\Bootstrap\AppManager;
use Forge\Core\Bootstrap\Autoloader;
use Forge\Core\Configuration\Config;
use Forge\Core\Contracts\Modules\ForgeEventDispatcherInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Contracts\Modules\RouterInterface;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeApi\ApiRouter;
use Forge\Modules\ForgeErrorHandler\ErrorModule;
use Forge\Modules\ForgeStorage\Contracts\StorageInterface;
use Forge\Core\Contracts\Modules\ErrorHandlerInterface;

class App
{

    /**
     * Get the Container
     *
     * @return Container
     */
    public static function getContainer(): Container
    {
        return Container::getContainer();
    }

    /**
     * Get the RouterInterface service from container
     *
     * @return RouterInterface
     */
    public static function router(): RouterInterface
    {
        return Container::getContainer()->get(RouterInterface::class);
    }

    /**
     * Get the RouterInterface service from container
     *
     * @return ApiRouter
     */
    public static function apiRouter(): ApiRouter
    {
        return Container::getContainer()->get(ApiRouter::class);
    }

    /**
     * Get the ForgeEventDispatcher service from container
     *
     * @return ForgeEventDispatcherInterface
     */
    public static function dispatchManager(): ForgeEventDispatcherInterface
    {
        return Container::getContainer()->get(ForgeEventDispatcherInterface::class);
    }

    /**
     * Get the Config service from container
     *
     * @return Config
     */
    public static function config(): Config
    {
        return Container::getContainer()->get(Config::class);
    }

    /**
     * Get the Storage service from container
     *
     * @return StorageInterface
     */
    public static function storage(): StorageInterface
    {
        return Container::getContainer()->get(StorageInterface::class);
    }

    /**
     * Get the RouterInterface service from container
     *
     * @return DatabaseInterface
     */
    public static function db(): DatabaseInterface
    {
        return Container::getContainer()->get(DatabaseInterface::class);
    }

    /**
     * Get the AppManager service from container
     *
     * @return AppManager
     */
    public static function appManager(): AppManager
    {
        return Container::getContainer()->get(AppManager::class);
    }

    /**
     * Get the ConsoleKernel service from container
     *
     * @return ConsoleKernel
     */
    public static function consoleKernel(): ConsoleKernel
    {
        return Container::getContainer()->get(ConsoleKernel::class);
    }

    /**
     * Register your apps into the autoloader
     *
     * @param array $appRegistry
     * @param string $basePath
     * @return void
     */
    public static function registerAppNamespace(array $appRegistry, string $basePath): void
    {
        foreach ($appRegistry as $appConfig) {
            $namespace = ucfirst($appConfig['name']);
            $appPath = $basePath . '/' . $appConfig['path'];
            Autoloader::addNamespace($namespace, $appPath);
        }
    }

    /**
     * Get the content of a given file
     *
     * @param array $filePath
     * @return mixed
     */
    public static function getFileContent($filePath): mixed
    {
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }

        return '';
    }

    /**
     * Gets the value of an environment variable.
     *
     * @param string $key The name of the environment variable.
     * @param string|null $default The default value to return if the variable is not set.
     * @return string|null The value of the environment variable, or the default value if not set, or null if no default.
     */
    public static function env(string $key, ?string $default = null): ?string
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        return $default;
    }

    /**
     * Register Error Handler
     *
     * @return void
     */
    public static function isErrorHandlerEnabled(): bool
    {
        if (class_exists(ErrorModule::class)) {
            $container = self::getContainer();
            $container->instance(ErrorHandlerInterface::class, function ($container) {
                return new ErrorModule($container->get(Config::class));
            });
            return true;
        }
        return false;
    }

}