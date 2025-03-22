<?php
declare(strict_types=1);

namespace App\Modules\ExampleModule\Commands;

use Forge\CLI\Command;
use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'example:greet', description: 'An example command to greet the user')]
class ExampleCommand extends Command
{
	public function __construct(private Config $config)
	{
		$settingOne = $config->get('example_module.setting_one');
		echo $settingOne;
	}
	public function execute(array $args): int
	{
		$name = $this->argument('name', $args) ?? 'Guest';
		$this->info("Hello, " . $name . " from the Example Module");
		return 0;
	}
}