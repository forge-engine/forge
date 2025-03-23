<?php
declare(strict_types=1);

namespace App\Modules\ForgeLogger\Commands;

use Forge\CLI\Command;
use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'forge-logger:greet', description: 'An example command to greet the user')]
class ForgeLoggerCommand extends Command
{
	public function __construct(private Config $config)
	{
		$settingOne = $config->get('forgelogger.example');
	}
	public function execute(array $args): int
	{
		$name = $this->argument('name', $args) ?? 'Guest';
		$this->info("Hello, " . $name . " from the ForgeLogger");
		return 0;
	}
}