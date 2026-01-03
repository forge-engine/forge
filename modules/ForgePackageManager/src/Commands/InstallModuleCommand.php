<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Commands;

use App\Modules\ForgePackageManager\Services\PackageManagerService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Throwable;

#[Cli(
    command: 'package:install-module',
    description: 'Install a module from the registry',
    usage: 'package:install-module --module=<module-name[@version]> [--force]',
    examples: [
        'package:install-module --module=my-module',
        'package:install-module --module=my-module@1.2.0',
        'package:install-module --module=my-module --force'
    ]
)]
final class InstallModuleCommand extends Command
{
    use Wizard;

    #[Arg(
        name: 'module',
        description: 'Module name with optional version (e.g., module-name[@1.0.0])',
        required: true
    )]
    private string $moduleNameVersion;

    #[Arg(
        name: 'force',
        description: 'Force bypass cache',
        default: false,
        required: false
    )]
    private bool $force;

    #[Arg(
        name: 'debug',
        description: 'Show debug information',
        default: false,
        required: false
    )]
    private bool $debug = false;

    public function __construct(private readonly PackageManagerService $packageManagerService)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        $this->packageManagerService->setDebugMode($this->debug);

        [$moduleName, $version] = explode('@', $this->moduleNameVersion) + [1 => null];

        try {
            $force = $this->force ? 'force' : '';
            $this->packageManagerService->installModule($moduleName, $version, $force);
            return 0;
        } catch (Throwable $e) {
            $this->error("Error installing module: " . $e->getMessage());
            return 1;
        }
    }
}