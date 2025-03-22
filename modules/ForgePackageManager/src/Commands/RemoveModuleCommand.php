<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Commands;

use App\Modules\ForgePackageManager\Services\PackageManagerService;
use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'package:remove-module', description: 'Remove an installed module')]
final class RemoveModuleCommand extends Command
{
    public function __construct(private PackageManagerService $packageManagerService)
    {
    }

    public function execute(array $args): int
    {
        if (empty($args[0])) {
            $this->error("Module name is required. Usage: forge remove:module <module-name>");
            return 1;
        }
        $moduleName = $args[0];

        try {
            $this->packageManagerService->removeModule($moduleName);

            $this->success("Module removed successfully.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
