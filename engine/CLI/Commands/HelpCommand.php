<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\CLICommand;
use ReflectionClass;

#[Service]
#[CLICommand(name: 'help', description: 'Displays help for available commands.')]
class HelpCommand extends Command
{
    public function __construct(private Container $container) {}

    /**
     * @throws ReflectionException
     */
    public function execute(array $commandClasses): int
    {
        $this->info("Forge Framework CLI Tool");
        $this->info("Available commands:");

        foreach ($commandClasses as $name => $commandInfo) {
            $commandClass = $commandInfo[0];
            $reflectionClass = new ReflectionClass($commandClass);
            $commandAttribute = $reflectionClass->getAttributes(CLICommand::class)[0] ?? null;
        
            if ($commandAttribute) {
                $commandInstance = $commandAttribute->newInstance();
                echo sprintf(
                    "  %-20s %s\n",
                    $name,
                    $commandInstance->description
                );
            }
        }

        return 0;
    }
}
