<?php

namespace Forge\Core\Module;

use Forge\Core\Bootstrap\AppManager;
use Forge\Core\Bootstrap\Autoloader;
use Forge\Core\Configuration\Config;
use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\Framework;
use RuntimeException;

class ModuleLoader
{
    private static ?ModuleLoader $instance = null;
    private array $modules = [];
    private Container $container;
    private AppManager $appManager;

    public function __construct(Container $container, AppManager $appManager)
    {
        $this->container = $container;
        $this->appManager = $appManager;
    }

    public static function getInstance(Container $container, AppManager $appManager): ModuleLoader
    {
        if (self::$instance === null) {
            self::$instance = new self($container, $appManager);
            self::$instance->loadModules();
            self::$instance->triggerModuleLifecycleEvents('afterConfigLoaded');
        }
        return self::$instance;
    }

    private function loadModules(): void
    {
        $moduleDirectory = BASE_PATH . '/modules';
        $this->triggerModuleLifecycleEvents('beforeModuleLoad');
        $this->loadFromDirectory($moduleDirectory);
        $this->triggerModuleLifecycleEvents('afterModuleLoad');
    }

    public function loadFromDirectory(string $directory): void
    {
        $manifests = [];
        foreach (glob("{$directory}/*/forge.json") as $manifestPath) {
            $manifest = new ModuleManifest($manifestPath);

            if ($manifest->getCore() === true) {
                continue;
            }

            $manifests[] = $manifest;
        }

        usort($manifests, function ($a, $b) {
            $orderA = $a->getOrder() ?? PHP_INT_MAX;
            $orderB = $b->getOrder() ?? PHP_INT_MAX;
            return $orderA <=> $orderB;
        });

        foreach ($manifests as $manifest) {
            $moduleDir = dirname($manifest->manifestPath);
            $this->loadModule($moduleDir, $manifest);
        }
    }

    public function triggerModuleLifecycleEvents(string $event): void
    {
        $this->appManager->trigger($event, $this->container);
    }

    private function loadModule(string $moduleDir, ModuleManifest $manifest): void
    {
        $frameworkCompatibility = $manifest->getCompatibility()['framework'] ?? null;
        if ($frameworkCompatibility) {
            $currentFrameworkVersion = Framework::version();
            if (!Framework::isVersionCompatible($currentFrameworkVersion, $frameworkCompatibility)) {
                throw new RuntimeException(
                    "Module '{$manifest->getName()}' is not compatible with the current framework version. " .
                    "Requires framework version: {$frameworkCompatibility}, current version: {$currentFrameworkVersion}"
                );
            }
        }

        $phpCompatibility = $manifest->getPhpCompatibility();
        if ($phpCompatibility) {
            $currentPhpVersion = PHP_VERSION;
            if (!Framework::isPhpVersionCompatible($currentPhpVersion, $phpCompatibility)) {
                throw new RuntimeException(
                    "Module '{$manifest->getName()}' is not compatible with the current PHP version. " .
                    "Requires PHP version: {$phpCompatibility}, current version: {$currentPhpVersion}"
                );
            }
        }

        foreach ($manifest->getLifecycleHooks() as $event) {
            $methodName = 'on' . ucfirst($event);
            $moduleClass = $manifest->getClass();

            if (method_exists($moduleClass, $methodName)) {
                $reflectMethod = new \ReflectionMethod($moduleClass, $methodName);
                $params = $reflectMethod->getParameters();

                if (count($params) === 1 && (string)$params[0]->getType() === Container::class) {
                    $callback = function (Container $container) use ($moduleClass, $methodName, $manifest) {
                        $moduleInstance = new $moduleClass($manifest);
                        return $moduleInstance->$methodName($container);
                    };
                } else {
                    $callback = [$moduleClass, $methodName];
                }

                $this->appManager->addHook($event, $callback);
            } else {
                error_log("Warning: Method {$methodName} not found in class {$moduleClass} for event {$event}");
            }
        }

        foreach ($manifest->getRequires() as $dependency) {
            if (!$this->container->has($dependency)) {
                throw new RuntimeException("Module {$manifest->getName()} requires {$dependency}");
            }
        }

        $config = $this->container->get(Config::class);
        $config->mergeModuleDefaults($manifest->getConfigDefaults());

        foreach ($manifest->getCli()['commands'] as $command) {
            $this->container->bind("cli.command.{$command}", CommandInterface::class);
        }
        $moduleClass = $manifest->getClass();
        if (!class_exists($moduleClass)) {
            throw new RuntimeException("Module class {$moduleClass} not found");
        }

        $module = new $moduleClass($manifest);
        $this->triggerModuleLifecycleEvents('beforeModuleRegister');
        $module->register($this->container);
        $this->triggerModuleLifecycleEvents('afterModuleRegister');

        $this->modules[$manifest->getName()] = $module;

        $moduleNamespace = str_replace('-', '\\', $manifest->getName());
        Autoloader::addNamespace(
            $moduleNamespace,
            $moduleDir . '/' . $manifest->getPaths()['src']
        );

        foreach ($manifest->getProvides() as $interface) {
            $this->container->bind($interface, $manifest->getClass());
        }
    }

    public function getModules(): array
    {
        return $this->modules;
    }


}
