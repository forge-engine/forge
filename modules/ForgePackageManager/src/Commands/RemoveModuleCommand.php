<?php
declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Commands;

use App\Modules\ForgePackageManager\Services\PackageManagerService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;
use Forge\Core\Helpers\Strings;
use Throwable;

#[Cli(
    command: 'package:remove-module',
    description: 'Remove an installed module',
    usage: 'package:remove-module [--module=<module-name>] [--force]',
    examples: [
        'package:remove-module --module=my-module',
        'package:remove-module --module=my-module --force',
        'package:remove-module'
    ]
)]
final class RemoveModuleCommand extends Command
{
    use Wizard;

    #[Arg(
        name: 'module',
        description: 'Name of the module to remove',
        required: false
    )]
    private ?string $moduleName = null;

    #[Arg(
        name: 'force',
        description: 'Skip the destructive-action confirmation',
        default: false,
        required: false
    )]
    private bool $force = false;

    #[Arg(
        name: 'debug',
        description: 'Show debug information',
        default: false,
        required: false
    )]
    private bool $debug = false;

    public function __construct(
        private readonly PackageManagerService $packageManager,
        private readonly TemplateGenerator $templateGenerator
    ) {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        $this->packageManager->setDebugMode($this->debug);

        if ($this->moduleName === null) {
            $this->runWizard();
            return 0;
        }

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

    private function runWizard(): void
    {
        $installedModules = $this->getInstalledModules();

        if (empty($installedModules)) {
            $this->error("No modules are currently installed.");
            return;
        }

        $moduleOptions = [];
        foreach ($installedModules as $moduleName) {
            $description = $this->getModuleDescription($moduleName);
            $displayName = $description ? "{$moduleName} ({$description})" : $moduleName;
            $moduleOptions[] = $displayName;
        }

        $selectedModuleDisplay = $this->templateGenerator->selectFromList(
            "Select a module to remove",
            $moduleOptions,
            $moduleOptions[0] ?? null
        );

        if ($selectedModuleDisplay === null) {
            $this->info('Removal cancelled.');
            return;
        }

        $selectedIndex = array_search($selectedModuleDisplay, $moduleOptions, true);
        $this->moduleName = $installedModules[$selectedIndex];

        if (!$this->force && !$this->confirmDestructiveAction()) {
            $this->warning('Module removal aborted.');
            return;
        }

        try {
            $this->packageManager->removeModule($this->moduleName);
            $this->success("Module '{$this->moduleName}' removed successfully.");
        } catch (Throwable $e) {
            $this->error('Error removing module: ' . $e->getMessage());
        }
    }

    private function getInstalledModules(): array
    {
        $forgeJsonPath = BASE_PATH . '/forge.json';
        if (!file_exists($forgeJsonPath)) {
            return [];
        }

        $content = file_get_contents($forgeJsonPath);
        $config = json_decode($content, true);

        if (!is_array($config) || !isset($config['modules']) || !is_array($config['modules'])) {
            return [];
        }

        return array_keys($config['modules']);
    }

    private function getModuleDescription(string $moduleName): ?string
    {
        $moduleFolderName = Strings::toPascalCase($moduleName);
        $moduleForgeJsonPath = BASE_PATH . "/modules/{$moduleFolderName}/forge.json";

        if (!file_exists($moduleForgeJsonPath)) {
            return null;
        }

        $content = file_get_contents($moduleForgeJsonPath);
        $moduleConfig = json_decode($content, true);

        if (!is_array($moduleConfig) || !isset($moduleConfig['description'])) {
            return null;
        }

        return $moduleConfig['description'];
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