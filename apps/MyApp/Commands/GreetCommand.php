<?php

namespace MyApp\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;

class GreetCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'app:greet';
    }

    public function getDescription(): string
    {
        return 'Greet command from app';
    }

    /**
     * @param array<int,mixed> $args
     */
    public function execute(array $args): int
    {
        $name = $args[0] ?? 'Guest';
        $this->info("Hello, {$name} from MyApp!\n");
        return 0;
    }
}