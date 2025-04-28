<?php

declare(strict_types=1);

namespace App\Modules\ForgeUI\Commands;

use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'forge-ui:greet', description: 'An example command to greet the user')]
class ForgeUICommand extends Command
{
    public function __construct()
    {
    }
    public function execute(array $args): int
    {
        $name = $this->argument('name', $args) ?? 'Guest';
        $this->info("Hello, " . $name . " from the ForgeUI");
        return 0;
    }
}
