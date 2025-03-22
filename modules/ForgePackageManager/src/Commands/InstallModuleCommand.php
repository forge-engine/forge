<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Commands;

use App\Modules\ForgePackageManager\Services\PackageManagerService;
use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'package:install-module', description: 'Install a module from the registry')]
final class InstallModuleCommand extends Command
{
    public function __construct(private PackageManagerService $packageManagerService)
    {
    }
    public function execute(array $args): int
    {
        $this->info("You can bypass the cache by adding force to the end: php forge.php install:module module-name force");
        if (empty($args[0])) {
            $this->error("Module name is required. Usage: php forge.php install:module <module-name>[@version]");
            return 1;
        }

        $moduleNameVersion = $args[0];
        $parts = explode('@', $moduleNameVersion);
        $moduleName = $parts[0];
        $forceCache = $args[1] ?? null;
        $version = $parts[1] ?? null;


        try {
            $this->packageManagerService->installModule($moduleName, $version, $forceCache);
            return 0;
        } catch (\Throwable $e) {
            $this->error("Error installing module: " . $e->getMessage());
            return 1;
        }
    }
}
