<?php

declare(strict_types=1);

namespace Forge\CLI;

use Forge\CLI\Commands\ClearCacheCommand;
use Forge\CLI\Commands\HelpCommand;
use Forge\CLI\Commands\MakeMigrationCommand;
use Forge\CLI\Commands\ServeCommand;
use Forge\CLI\Commands\MigrateCommand;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\CLICommand;
use ReflectionClass;

final class Application
{
    private array $commands = [];
    private Container $container;
    private static int $instanceCount = 0;
    private int $instanceId;
    private static ?self $instance = null;
    

    private function __construct(Container $container)
    {
        $this->instanceId = ++self::$instanceCount;
        $this->container = $container;
        $this->registerCoreCommands();
    }
    
    public static function getInstance(Container $container): self
    {
        if (!self::$instance) {
            self::$instance = new self($container);
        }
        return self::$instance;
    }
    
    public function getCommands(): array
    {
        return $this->commands;
    }
    
    public function getInstanceId(): int
    {
        return $this->instanceId;
    }

    /**
     * @throws \ReflectionException
     */
    public function run(array $argv): int
    {
        if (count($argv) < 2) {
            $this->showHelp();
            return 1;
        }

        $commandName = $argv[1];

        if ($commandName === "help") {
            $helpCommand = $this->container->make(HelpCommand::class);
            $helpCommand->execute($this->commands);
            return 0;
        }

        foreach ($this->commands as $name => $commandInfo) {
            if ($name === $commandName) {
                $commandClass = $commandInfo[0];
                $command = $this->container->make($commandClass);
                $args = array_slice($argv, 2);
                $command->execute($args);
                return 0;
            }
        }

        $this->showHelp();

        echo "Command not found: $commandName\n";
        return 1;
    }

    private function registerCoreCommands(): void
    {
        $this->registerCommand(ServeCommand::class);
        $this->registerCommand(MakeMigrationCommand::class);
        $this->registerCommand(MigrateCommand::class);
        $this->registerCommand(ClearCacheCommand::class);
        //$this->registerCommand(RollbackCommand::class);
    }

    /**
     * @throws ReflectionException
     */
    private function registerCommand(string $commandClass): void
    {
        $reflectionClass = new ReflectionClass($commandClass);
        $commandAttribute = $reflectionClass->getAttributes(CLICommand::class)[0] ?? null;
        
        if ($commandAttribute) {
            $commandInterface = $commandAttribute->newInstance();
            $this->container->register($commandClass);
            $this->commands[$commandInterface->name] = [$commandClass, $commandInterface->description];
        }
    }
    
    public function registerCommandClass(string $commandClass, string $name, string $description)
    {
        $reflectionClass = new ReflectionClass($commandClass);
        $commandAttribute = $reflectionClass->getAttributes(CLICommand::class)[0] ?? null;
        
        if ($commandAttribute) {
            $commandInstance = $commandAttribute->newInstance();
            $this->container->register($commandClass);
            $this->commands[$commandInstance->name] = [$commandClass, $commandInstance->description];
        }
    }

    /**
     * @throws \ReflectionException
     */
    private function showHelp(): void
    {
        $helpCommand = $this->container->make(HelpCommand::class);
        $helpCommand->execute($this->commands);
    }
}
