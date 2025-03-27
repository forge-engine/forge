<?php

declare(strict_types=1);

namespace App\Modules\ForgeErrorHandler\Commands;

use Forge\CLI\Command;
use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'forge-error-handler:greet', description: 'An example command to greet the user')]
class ForgeErrorHandlerCommand extends Command
{
    public function __construct(private Config $config)
    {
        $settingOne = $config->get('forgeerrorhandler.example');
    }
    public function execute(array $args): int
    {
        $name = $this->argument('name', $args) ?? 'Guest';
        $this->info("Hello, " . $name . " from the ForgeErrorHandler");
        return 0;
    }
}
