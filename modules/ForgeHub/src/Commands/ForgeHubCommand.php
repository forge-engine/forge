<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Commands;

use Forge\CLI\Command;
use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'forge-hub:greet', description: 'An example command to greet the user')]
class ForgeHubCommand extends Command
{
    public function __construct(private Config $config)
    {
        $settingOne = $config->get('forgehub.example');
    }
    public function execute(array $args): int
    {
        $name = $this->argument('name', $args) ?? 'Guest';
        $this->info("Hello, " . $name . " from the ForgeHub");
        return 0;
    }
}
