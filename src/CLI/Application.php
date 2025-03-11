<?php

declare(strict_types=1);

namespace Forge\CLI;

use Forge\CLI\Commands\HelpCommand;
use Forge\CLI\Commands\MakeMigrationCommand;
use Forge\CLI\Commands\ServeCommand;
use Forge\CLI\Commands\MigrateCommand;
use Forge\Core\DI\Container;

final class Application
{
    private array $commands = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->registerCoreCommands();
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

        if ($commandName === 'help') {
            $helpCommand = $this->container->make(HelpCommand::class);
            $helpCommand->execute($this->commands);
            return 0;
        }

        foreach ($this->commands as $commandClass) {
            $command = $this->container->make($commandClass);
            if ($command->getName() === $commandName) {
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
        $this->registerCommand(HelpCommand::class);
        $this->registerCommand(MakeMigrationCommand::class);
        $this->registerCommand(MigrateCommand::class);
        //$this->registerCommand(RollbackCommand::class);
    }

    /**
     * @throws \ReflectionException
     */
    private function registerCommand(string $commandClass): void
    {
        $this->container->register($commandClass);
        $this->commands[] = $commandClass;
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
