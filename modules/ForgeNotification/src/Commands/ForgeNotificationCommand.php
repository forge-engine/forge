<?php
declare(strict_types=1);

namespace App\Modules\ForgeNotification\Commands;

use Forge\CLI\Command;
use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'forge-notification:greet', description: 'An example command to greet the user')]
class ForgeNotificationCommand extends Command
{
	public function __construct(private Config $config)
	{
		$settingOne = $config->get('forgenotification.example');
	}
	public function execute(array $args): int
	{
		$name = $this->argument('name', $args) ?? 'Guest';
		$this->info("Hello, " . $name . " from the ForgeNotification");
		return 0;
	}
}