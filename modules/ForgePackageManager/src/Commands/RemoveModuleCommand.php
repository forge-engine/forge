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
    command: 'package:remove-module',
    description: 'Remove an installed module',
    usage: 'package:remove-module <module-name>',
    examples: [
        'package:remove-module my-module'
    ]
)]
final class RemoveModuleCommand extends Command
{
    use Wizard;

    #[Arg(
        name: 'module',
        description: 'Name of the module to remove',
        required: true
    )]
    private string $moduleName;

    public function __construct(private readonly PackageManagerService $packageManagerService)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        try {
            $this->packageManagerService->removeModule($this->moduleName);
            $this->success("Module '{$this->moduleName}' removed successfully.");
            return 0;
        } catch (Throwable $e) {
            $this->error("Error removing module: " . $e->getMessage());
            return 1;
        }
    }
}