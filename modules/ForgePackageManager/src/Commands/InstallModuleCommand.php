<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Commands;

use App\Modules\ForgePackageManager\Services\PackageManagerService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\Wizard;
use Forge\Core\Services\TemplateGenerator;
use Forge\Core\Services\GitService;
use Throwable;

    #[Cli(
        command: 'package:install-module',
        description: 'Install a module from the registry',
        usage: 'package:install-module [--module=<module-name[@version]>] [module-name[@version] ...] [--force]',
        examples: [
            'package:install-module --module=my-module',
            'package:install-module --module=my-module@1.2.0',
            'package:install-module --module=my-module --force',
            'package:install-module module-one module-two@1.0.0',
            'package:install-module'
        ]
    )]
final class InstallModuleCommand extends Command
{
    use Wizard;

    #[Arg(
        name: 'module',
        description: 'Module name with optional version (e.g., module-name[@1.0.0])',
        required: false
    )]
    private ?string $moduleNameVersion = null;

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

    public function __construct(
        private readonly PackageManagerService $packageManagerService,
        private readonly TemplateGenerator $templateGenerator,
        private readonly GitService $gitService
    ) {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        $this->packageManagerService->setDebugMode($this->debug);

        $modulesToInstall = $this->parseModules($args);

        if (empty($modulesToInstall)) {
            $this->runWizard();
            return 0;
        }

        $force = $this->force ? 'force' : '';
        $successCount = 0;
        $errorCount = 0;

        foreach ($modulesToInstall as $moduleData) {
            [$moduleName, $version] = $moduleData;
            
            if (!$this->force && !$this->confirmReinstallModule($moduleName, $version)) {
                $this->warning("Module installation aborted for '{$moduleName}'.");
                $errorCount++;
                continue;
            }
            
            try {
                $this->packageManagerService->installModule($moduleName, $version, $force);
                $successCount++;
            } catch (Throwable $e) {
                $this->error("Error installing module '{$moduleName}': " . $e->getMessage());
                $errorCount++;
            }
        }

        if ($errorCount > 0) {
            return 1;
        }

        return 0;
    }

    private function parseModules(array $args): array
    {
        $modules = [];

        if ($this->moduleNameVersion !== null) {
            [$moduleName, $version] = explode('@', $this->moduleNameVersion) + [1 => null];
            $modules[] = [$moduleName, $version];
        }

        foreach ($args as $arg) {
            if (!str_starts_with($arg, '--') && !str_starts_with($arg, '-')) {
                [$moduleName, $version] = explode('@', $arg) + [1 => null];
                $modules[] = [$moduleName, $version];
            }
        }

        return $modules;
    }

    private function runWizard(): void
    {
        $choice = $this->templateGenerator->selectFromList(
            "How would you like to proceed?",
            ['Enter module name manually', 'Browse available modules'],
            'Browse available modules'
        );

        if ($choice === null) {
            $this->info('Installation cancelled.');
            return;
        }

        if ($choice === 'Enter module name manually') {
            $this->handleManualEntry();
        } else {
            $this->handleBrowseModules();
        }
    }

    private function handleManualEntry(): void
    {
        $moduleInput = $this->templateGenerator->askQuestion("Enter module name (with optional version, e.g., my-module@1.0.0)", "");
        
        if (empty($moduleInput)) {
            $this->error("Module name is required.");
            return;
        }

        [$moduleName, $version] = explode('@', $moduleInput) + [1 => null];

        if ($version === null) {
            $moduleInfo = $this->packageManagerService->getModuleInfo($moduleName);
            if ($moduleInfo && isset($moduleInfo['versions'])) {
                $versionList = array_keys($moduleInfo['versions']);
                $versionOptions = $this->buildVersionOptions($versionList, null, $moduleName);
                
                $selectedVersion = $this->templateGenerator->selectFromList(
                    "Select version",
                    $versionOptions,
                    'latest'
                );
                
                if ($selectedVersion === null) {
                    $this->info('Installation cancelled.');
                    return;
                }
                
                $version = $selectedVersion === 'latest' ? null : $this->extractVersionFromOption($selectedVersion);
            }
        }

        if (!$this->force && !$this->confirmReinstallModule($moduleName, $version)) {
            $this->warning('Module installation aborted.');
            return;
        }

        $forceChoice = $this->templateGenerator->selectFromList(
            "Force download (bypass cache)?",
            ['No', 'Yes'],
            'No'
        );

        $force = $forceChoice === 'Yes' ? 'force' : '';

        try {
            $this->packageManagerService->installModule($moduleName, $version, $force);
        } catch (Throwable $e) {
            $this->error("Error installing module: " . $e->getMessage());
        }
    }

    private function handleBrowseModules(): void
    {
        $registries = $this->packageManagerService->getRegistries();

        if (empty($registries)) {
            $this->error("No package registries configured. Please configure registries in config/source_list.php");
            return;
        }

        $registryOptions = [];
        foreach ($registries as $registry) {
            $name = $registry['name'] ?? 'Unknown Registry';
            $description = $registry['description'] ?? null;
            $displayName = $description ? "{$name} ({$description})" : $name;
            $registryOptions[] = $displayName;
        }

        $selectedRegistryDisplay = $this->templateGenerator->selectFromList(
            "Select a registry",
            $registryOptions,
            $registryOptions[0] ?? null
        );

        if ($selectedRegistryDisplay === null) {
            $this->info('Installation cancelled.');
            return;
        }

        $selectedIndex = array_search($selectedRegistryDisplay, $registryOptions, true);
        $selectedRegistry = $registries[$selectedIndex];

        $this->info("Fetching modules from registry...");
        $modulesData = $this->packageManagerService->getAllModulesFromRegistry($selectedRegistry);

        if (!is_array($modulesData) || empty($modulesData)) {
            $this->error("No modules found in the selected registry.");
            return;
        }

        $moduleNames = array_keys($modulesData);
        sort($moduleNames);

        $choice = $this->templateGenerator->selectFromList(
            "How would you like to proceed?",
            ['Select a single module', 'Select multiple modules (latest)'],
            'Select a single module'
        );

        if ($choice === null) {
            $this->info('Installation cancelled.');
            return;
        }

        if ($choice === 'Select multiple modules (latest)') {
            $this->handleMultiSelectInstall($moduleNames, $selectedRegistry);
        } else {
            $this->handleSingleSelectInstall($moduleNames, $modulesData, $selectedRegistry);
        }
    }

    private function handleSingleSelectInstall(array $moduleNames, array $modulesData, array $selectedRegistry): void
    {
        $selectedModuleName = $this->templateGenerator->selectFromList(
            "Select a module",
            $moduleNames,
            $moduleNames[0] ?? null
        );

        if ($selectedModuleName === null) {
            $this->info('Installation cancelled.');
            return;
        }

        $moduleInfo = $modulesData[$selectedModuleName];
        $versionList = isset($moduleInfo['versions']) ? array_keys($moduleInfo['versions']) : [];
        
        if (empty($versionList)) {
            $this->error("No versions available for module '{$selectedModuleName}'.");
            return;
        }

        $versionOptions = $this->buildVersionOptions($versionList, $selectedRegistry, $selectedModuleName);
        
        $selectedVersion = $this->templateGenerator->selectFromList(
            "Select version",
            $versionOptions,
            'latest'
        );

        if ($selectedVersion === null) {
            $this->info('Installation cancelled.');
            return;
        }

        $version = $selectedVersion === 'latest' ? null : $this->extractVersionFromOption($selectedVersion);

        if (!$this->force && !$this->confirmReinstallModule($selectedModuleName, $version)) {
            $this->warning('Module installation aborted.');
            return;
        }

        $forceChoice = $this->templateGenerator->selectFromList(
            "Force download (bypass cache)?",
            ['No', 'Yes'],
            'No'
        );

        $force = $forceChoice === 'Yes' ? 'force' : '';

        try {
            $this->packageManagerService->installModule($selectedModuleName, $version, $force);
        } catch (Throwable $e) {
            $this->error("Error installing module: " . $e->getMessage());
        }
    }

    private function handleMultiSelectInstall(array $moduleNames, array $selectedRegistry): void
    {
        $selectedModuleNames = $this->templateGenerator->selectMultipleFromList(
            "Select modules to install (latest)",
            $moduleNames
        );

        if ($selectedModuleNames === null || empty($selectedModuleNames)) {
            $this->info('Installation cancelled.');
            return;
        }

        $forceChoice = $this->templateGenerator->selectFromList(
            "Force download (bypass cache)?",
            ['No', 'Yes'],
            'No'
        );

        $force = $forceChoice === 'Yes' ? 'force' : '';

        $this->line('');
        $this->info("Installing " . count($selectedModuleNames) . " module(s) (latest versions)...");
        $this->line('');

        $successCount = 0;
        $errorCount = 0;

        foreach ($selectedModuleNames as $moduleName) {
            if (!$this->force && !$this->confirmReinstallModule($moduleName, null)) {
                $this->warning("Skipping '{$moduleName}'.");
                $errorCount++;
                continue;
            }

            try {
                $this->packageManagerService->installModule($moduleName, null, $force);
                $successCount++;
            } catch (Throwable $e) {
                $this->error("Error installing module '{$moduleName}': " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->line('');
        if ($successCount > 0) {
            $this->info("Successfully installed {$successCount} module(s).");
        }
        if ($errorCount > 0) {
            $this->warning("Failed to install {$errorCount} module(s).");
        }
    }

    private function buildVersionOptions(array $versions, ?array $registry, string $moduleName): array
    {
        $options = [];
        $options[] = 'latest';

        foreach ($versions as $version) {
            $commitMessage = $this->getCommitMessageForVersion($version, $registry, $moduleName);
            if ($commitMessage) {
                $options[] = "{$version} - {$commitMessage}";
            } else {
                $options[] = $version;
            }
        }

        return $options;
    }

    private function extractVersionFromOption(string $option): string
    {
        if ($option === 'latest') {
            return 'latest';
        }

        $parts = explode(' - ', $option, 2);
        return $parts[0];
    }

    private function getCommitMessageForVersion(string $version, ?array $registry, string $moduleName): ?string
    {
        if ($registry === null || ($registry['type'] ?? '') !== 'git') {
            return null;
        }

        $registryUrl = $registry['url'] ?? '';
        if (empty($registryUrl)) {
            return null;
        }

        $registryPath = $this->getLocalRegistryPath($registryUrl);
        if ($registryPath === null) {
            return null;
        }

        $moduleNameKebab = $this->toKebabCase($moduleName);
        $versionFile = "modules/{$moduleNameKebab}/{$version}/{$version}.zip";
        
        $commitMessage = $this->gitService->getLastCommitMessage($registryPath, $versionFile);
        
        return $commitMessage;
    }

    private function getLocalRegistryPath(string $registryUrl): ?string
    {
        if (str_contains($registryUrl, 'github.com')) {
            $pattern = '#github\.com[:/]([^/]+)/([^/.]+)#';
            if (preg_match($pattern, $registryUrl, $matches)) {
                $org = $matches[1];
                $repo = $matches[2];
                $possiblePath = BASE_PATH . "/storage/framework/cache/registries/{$org}-{$repo}";
                
                if ($this->gitService->isGitRepository($possiblePath)) {
                    return $possiblePath;
                }
            }
        }

        $cachePath = BASE_PATH . '/storage/framework/cache/registries';
        if (!is_dir($cachePath)) {
            return null;
        }

        $dirs = glob($cachePath . '/*', GLOB_ONLYDIR);
        if ($dirs === false) {
            return null;
        }

        foreach ($dirs as $dir) {
            if ($this->gitService->isGitRepository($dir)) {
                $remoteUrl = $this->gitService->getRemoteUrl($dir, 'origin');
                if ($remoteUrl && $this->urlsMatch($remoteUrl, $registryUrl)) {
                    return $dir;
                }
            }
        }

        return null;
    }

    private function urlsMatch(string $url1, string $url2): bool
    {
        $normalize = function(string $url): string {
            $url = str_replace(['https://', 'http://', 'git@'], '', $url);
            $url = str_replace(':', '/', $url);
            $url = rtrim($url, '.git');
            $url = rtrim($url, '/');
            return strtolower($url);
        };

        return $normalize($url1) === $normalize($url2);
    }

    private function toKebabCase(string $string): string
    {
        $string = preg_replace('/([a-z])([A-Z])/', '$1-$2', $string);
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        return trim($string, '-');
    }

    private function getInstalledModuleVersion(string $moduleName): ?string
    {
        $forgeJsonPath = BASE_PATH . '/forge.json';
        if (!file_exists($forgeJsonPath)) {
            return null;
        }

        $content = file_get_contents($forgeJsonPath);
        $config = json_decode($content, true);

        if (!is_array($config) || !isset($config['modules']) || !is_array($config['modules'])) {
            return null;
        }

        return $config['modules'][$moduleName] ?? null;
    }

    private function resolveVersionToInstall(string $moduleName, ?string $version): ?string
    {
        if ($version === null || $version === 'latest') {
            $moduleInfo = $this->packageManagerService->getModuleInfo($moduleName);
            if ($moduleInfo && isset($moduleInfo['latest'])) {
                return $moduleInfo['latest'];
            }
            return null;
        }

        return $version;
    }

    private function confirmReinstallModule(string $moduleName, ?string $versionToInstall): bool
    {
        $installedVersion = $this->getInstalledModuleVersion($moduleName);
        if ($installedVersion === null) {
            return true;
        }

        $resolvedVersion = $this->resolveVersionToInstall($moduleName, $versionToInstall);
        if ($resolvedVersion === null || $resolvedVersion !== $installedVersion) {
            return true;
        }

        $this->line('');
        $this->warning(str_repeat('▓', 70));
        $this->warning('  D A N G E R   Z O N E');
        $this->warning(str_repeat('▓', 70));
        $this->line('');
        $this->warning("  Module: {$moduleName}");
        $this->warning("  Version: {$installedVersion}");
        $this->line('');
        $this->line('  – This module is already installed with version ' . $installedVersion . '.');
        $this->line('  – If you have modified the module, you may LOSE any changes you made.');
        $this->line('');
        $this->comment('  There is NO UNDO. Are you absolutely sure?');
        $this->line('');

        return $this->askYesNo('Type yes in UPPER-CASE to proceed', 'YES');
    }
}