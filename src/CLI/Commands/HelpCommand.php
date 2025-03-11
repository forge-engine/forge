<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;

#[Service]
class HelpCommand extends Command
{
    public function __construct(private Container $container) {}

    public static function getName(): string
    {
        return "help";
    }

    public static function getDescription(): string
    {
        return "Displays help for available commands.";
    }

    /**
     * @throws \ReflectionException
     */
    public function execute(array $commandClasses): int
    {
        $this->info("Forge Framework CLI Tool");
        $this->info("Available commands:");

        foreach ($commandClasses as $commandClass) {
            echo sprintf(
                "  %-20s %s\n",
                $commandClass::getName(),
                $commandClass::getDescription()
            );
        }

        return 0;
    }
}
