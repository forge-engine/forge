<?php

namespace Forge\Console;

use Forge\Console\Commands\KeyGenerateCommand;
use Forge\Console\Commands\PublishModuleResourcesCommand;
use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Console\Commands\ConfigCacheCommand;
use Forge\Console\Commands\ConfigClearCommand;
use Forge\Console\Commands\ListModulesCommand;
use Forge\Console\Commands\MakeModuleCommand;
use Forge\Console\Commands\ServeCommand;
use Forge\Core\Bootstrap\AppManager;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\App;
use Forge\Core\Helpers\Path;
use Forge\Core\Traits\OutputHelper;

class ConsoleKernel
{
    use OutputHelper;

    private static ?ConsoleKernel $instance = null;
    private Container $container;
    private array $commands = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->discoverCommands();
    }

    public static function init(Container $container): ConsoleKernel
    {
        if (self::$instance === null) {
            self::$instance = new ConsoleKernel($container);
        }
        return self::$instance;
    }

    public static function getConsoleKernel(): ConsoleKernel
    {
        if (self::$instance === null) {
            throw new \RuntimeException("ConsoleKernel not initialized. Call ConsoleKernel::init() first.");
        }
        return self::$instance;
    }

    private function discoverCommands(): void
    {
        $this->commands = [];

        $appRouteCommands = $this->getAppRouteCommands();
        $appCommands = $this->getAppCommands();
        $moduleCommands = $this->getModuleCommands();
        $coreCommands = $this->getCoreCommands();
        foreach ($appRouteCommands as $name => $command) {
            $this->registerCommand($name, $command);
        }
        foreach ($appCommands as $name => $command) {
            $this->registerCommand($name, $command);
        }
        foreach ($moduleCommands as $commandInstance) {
            if ($commandInstance instanceof CommandInterface) {
                $commandName = $commandInstance->getName();
                $this->registerCommand($commandName, $commandInstance);
            } else {
                error_log("[ConsoleKernel]: Warning: Tagged 'module.command' entry is not a CommandInterface instance.");
            }
        }
        foreach ($coreCommands as $name => $command) {
            $this->registerCommand($name, $command);
        }
    }

    private function getCoreCommands(): array
    {
        return [
            'list:modules' => new ListModulesCommand($this->container),
            'config:cache' => new ConfigCacheCommand(),
            'config:clear' => new ConfigClearCommand(),
            'key:generate' => new KeyGenerateCommand(),
            'make:module' => new MakeModuleCommand(),
            'publish' => new PublishModuleResourcesCommand(),
            'serve' => new ServeCommand(),
        ];
    }

    private function makeCommandList(): array
    {
        return [
            'make:module' => new MakeModuleCommand(),
        ];
    }

    private function getModuleCommands(): array
    {
        return $this->container->getTagged('module.command');
    }

    private function getAppCommands(): array
    {
        return $this->container->getTagged('app.command');
    }

    private function getAppRouteCommands(): array
    {
        $appCommands = [];
        $appManager = $this->container->get(AppManager::class);
        $apps = $appManager->getApps();

        foreach ($apps as $app) {
            $config = App::config();
            $consoleRouteFile = $config->get('routes.console', '');
            if ($consoleRouteFile) {
                $consoleRoutesPath = Path::basePath("/{$consoleRouteFile}");
                if (file_exists($consoleRoutesPath)) {
                    $commandDefinitions = require_once $consoleRoutesPath;
                    if (is_array($commandDefinitions)) {
                        foreach ($commandDefinitions as $commandName => $commandClass) {
                            if (class_exists($commandClass) && is_subclass_of($commandClass, CommandInterface::class)) {
                                $commandInstance = new $commandClass($this->container);
                                $this->registerCommand("app:$commandName", $commandInstance);
                            } else {
                                error_log("[ConsoleKernel]: Warning: Invalid command definition for '{$commandName}' in " . $consoleRoutesPath . ". Class '{$commandClass}' not found or not a CommandInterface.");
                            }
                        }
                    } else {
                        error_log("[ConsoleKernel]: Warning: Invalid console route file at " . $consoleRoutesPath . ". Should return an array of command definitions.");
                    }
                } else {
                    error_log("[ConsoleKernel]: Console route file not found: " . $consoleRoutesPath);
                }
            }

        }
        return [];
    }


    public function handle(array $args): int
    {
        $commandName = $args[1] ?? 'list:commands';
        $args = array_slice($args, 2);

        if (!isset($this->commands[$commandName])) {
            $this->showHelp();
            return 1;
        }

        return $this->commands[$commandName]->execute($args);
    }

    public function registerCommand(string $name, CommandInterface $command): void
    {
        $this->commands[$name] = $command;
    }

    private function showHelp(): void
    {
        $this->info('Available commands:');
        foreach ($this->commands as $command) {
            echo "  \033[32m{$command->getName()}\033[0m - {$command->getDescription()}\n";
        }
    }
}
