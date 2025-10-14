<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Commands;

use Forge\CLI\Command;
use Forge\Core\Config\Config;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'forge-multi-tenant:greet', description: 'An example command to greet the user')]
class ForgeMultiTenantCommand extends Command
{
	public function __construct(private Config $config)
	{
		$settingOne = $config->get('forgemultitenant.example');
	}
	public function execute(array $args): int
	{
		$name = $this->argument('name', $args) ?? 'Guest';
		$this->info("Hello, " . $name . " from the ForgeMultiTenant");
		return 0;
	}
}