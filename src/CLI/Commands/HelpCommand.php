<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;

#[Service]
class HelpCommand extends Command
{
    public function getName(): string
    {
        return 'help';
    }

    public function getDescription(): string
    {
        return 'Displays help for available commands.';
    }

    /**
     * @throws \ReflectionException
     */
    public function execute(array $args): int
    {
        $this->info("Forge Framework CLI Tool");
        $this->info("Available commands:");

        foreach ($args as $commandClass) {
            $command = Container::getInstance()->make($commandClass);
            echo sprintf(
                "  %-20s %s\n",
                $command->getName(),
                $command->getDescription()
            );
        }

        return 0;
    }
}
