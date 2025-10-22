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
    usage: 'package:remove-module --module=<module-name> [--force]',
    examples: [
        'package:remove-module --module=my-module',
        'package:remove-module --module=my-module --force'
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

    #[Arg(
        name: 'force',
        description: 'Skip the destructive-action confirmation',
        default: false,
        required: false
    )]
    private bool $force = false;

    public function __construct(private readonly PackageManagerService $packageManager)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        if (!$this->force && !$this->confirmDestructiveAction()) {
            $this->warning('Module removal aborted.');
            return 2;
        }

        try {
            $this->packageManager->removeModule($this->moduleName);
            $this->success("Module '{$this->moduleName}' removed successfully.");
            return 0;
        } catch (Throwable $e) {
            $this->error('Error removing module: ' . $e->getMessage());
            return 1;
        }
    }

    private function confirmDestructiveAction(): bool
    {
        $hasMigrations = $this->packageManager->moduleHasMigrations($this->moduleName);
        $hasSeeders = $this->packageManager->moduleHasSeeders($this->moduleName);
        $hasAssets = $this->packageManager->moduleHasAssets($this->moduleName);

        if (!$hasMigrations && !$hasSeeders && !$hasAssets) {
            return true;
        }

        $this->line('');
        $this->warning(str_repeat('▓', 70));
        $this->warning('  D A N G E R   Z O N E');
        $this->warning(str_repeat('▓', 70));
        $this->line('');

        if ($hasMigrations) {
            $this->line('  – This will ROLLBACK all migrations provided by the module.');
        }
        if ($hasSeeders) {
            $this->line('  – All seeded data will be LOST.');
        }
        if ($hasAssets) {
            $this->line('  – Published assets will be UNLINKED.');
        }

        $this->line('');
        $this->comment('  There is NO UNDO. Are you absolutely sure?');
        $this->line('');

        return $this->askYesNo('Type yes in UPPER-CASE to proceed', 'YES');
    }
}