<?php
declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Commands;

use App\Modules\ForgePackageManager\Services\PackageManagerService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Throwable;

#[Cli(
    command: 'package:install-project',
    description: 'Install modules from forge-lock.json',
    usage: 'package:install-project',
    examples: [
        'package:install-project  # Install all modules from lock file'
    ]
)]
final class InstallCommand extends Command
{
    use Wizard;
    
    public function __construct(private readonly PackageManagerService $packageManagerService)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        try {
            $this->packageManagerService->installFromLock();
            $this->success("Modules installed successfully");
            return 0;
        } catch (Throwable $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}