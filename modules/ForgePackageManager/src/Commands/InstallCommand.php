<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Commands;

use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;
use App\Modules\ForgePackageManager\Services\PackageManagerService;
use Throwable;

#[CLICommand(name: 'package:install-project', description: 'Install modules from forge-lock.json')]
final class InstallCommand extends Command
{
    public function __construct(private PackageManagerService $packageManagerService)
    {
    }

    public function execute(array $args): int
    {
        try {
            $this->packageManagerService->installFromLock();
            $this->success("Modules installed successfully");
            return 0;
        } catch (Throwable $e) {
            $this->error("Error :" . $e->getMessage());
            return 1;
        }
    }
}
