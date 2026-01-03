<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Services;

use App\Modules\ForgePackageManager\Contracts\PackageManagerInterface;
use App\Modules\ForgePackageManager\Sources\SourceFactory;
use App\Modules\ForgePackageManager\Sources\SourceInterface;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Strings;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use Forge\Traits\StringHelper;
use ReflectionClass;
use ReflectionException;
use ZipArchive;

#[Service]
#[Provides(interface: PackageManagerInterface::class, version: '1.0.0')]
#[Requires()]
final class PackageManagerService implements PackageManagerInterface
{
    use OutputHelper;
    use StringHelper;

    private const string FRAMEWORK_MODULE_NAME = 'forge-engine/framework';
    private const string PACKAGE_MANAGER_MODULE_NAME = 'forge-package-manager';

    private array $registries = [];
    private int $cacheTtl;
    private string $modulesPath;
    private string $cachePath;
    private string $integrityHash;
    private string $trustedSourcesPath;
    private bool $debugEnabled = false;

    public function __construct(private readonly Config $config)
    {
        $this->registries = $this->config->get('source_list.registry', []);
        $cacheTtlValue = $this->config->get('source_list.cache_ttl', 3600);
        $this->cacheTtl = is_array($cacheTtlValue) ? 3600 : (int)$cacheTtlValue;
        $this->modulesPath = BASE_PATH . '/modules/';
        $this->cachePath = BASE_PATH . '/storage/framework/cache/modules/';
        $this->trustedSourcesPath = BASE_PATH . '/storage/framework/trusted_sources.json';

        $this->ensureCacheDirectoryExists();
        $this->ensureModulesDirectoryExists();
        $this->ensureTrustedSourcesFileExists();
    }

    private function ensureCacheDirectoryExists(): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    private function ensureModulesDirectoryExists(): void
    {
        if (!is_dir($this->modulesPath)) {
            mkdir($this->modulesPath, 0755, true);
        }
    }

    private function cleanExpiredCache(): void
    {
        if (!is_dir($this->cachePath)) {
            return;
        }

        $files = glob($this->cachePath . '*.cache');
        if ($files === false) {
            return;
        }

        $now = time();
        $cleaned = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $age = $now - filemtime($file);
                if ($age >= $this->cacheTtl) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }

        if ($cleaned > 0) {
            $this->info("Cleaned {$cleaned} expired cache file(s).");
        }
    }

    private function ensureTrustedSourcesFileExists(): void
    {
        $dir = dirname($this->trustedSourcesPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!file_exists($this->trustedSourcesPath)) {
            file_put_contents($this->trustedSourcesPath, json_encode(['sources' => []], JSON_PRETTY_PRINT));
        }
    }

    private function readTrustedSources(): array
    {
        if (!file_exists($this->trustedSourcesPath)) {
            return ['sources' => []];
        }
        $content = file_get_contents($this->trustedSourcesPath);
        $data = json_decode($content, true);
        return is_array($data) ? $data : ['sources' => []];
    }

    private function writeTrustedSources(array $data): void
    {
        file_put_contents($this->trustedSourcesPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function isSourceTrusted(string $registryName): bool
    {
        $data = $this->readTrustedSources();
        return isset($data['sources'][$registryName]['trusted']) && $data['sources'][$registryName]['trusted'] === true;
    }

    private function trustSource(string $registryName): void
    {
        $data = $this->readTrustedSources();
        $data['sources'][$registryName] = [
            'trusted' => true,
            'trusted_at' => date('c')
        ];
        $this->writeTrustedSources($data);
    }

    private function promptUserInput(string $prompt, string $default = 'n'): string
    {
        echo $prompt;
        $input = trim(fgets(STDIN) ?: '');
        return $input ?: $default;
    }

    private function showPostInstallWarning(string $moduleName, string $registryName, int $commandCount): void
    {
        $this->line("");
        $this->warning("⚠️  POST-INSTALL SCRIPT WARNING ⚠️");
        $this->line("");
        $this->error("Module '{$moduleName}' from registry '{$registryName}' has {$commandCount} post-install command(s).");
        $this->error("These commands will execute with full system permissions.");
        $this->line("");
    }

    private function confirmPostInstallCommand(string $command, string $moduleName, string $registryName, int $commandIndex, int $totalCommands): string
    {
        $this->line("");
        $this->warning("⚠️  SECURITY WARNING ⚠️");
        $this->line("");
        $this->error("Module '{$moduleName}' from registry '{$registryName}' wants to execute:");
        $this->line("  Command: {$command}");
        $this->line("");
        $this->warning("This command will run with the same permissions as this process.");
        $this->warning("Only run commands from trusted sources.");
        $this->line("");
        $this->info("Options:");
        $this->line("  [Y]es - Run this command");
        $this->line("  [N]o - Skip this command");
        $this->line("  [A]ll - Accept all remaining commands");
        $this->line("  [R]eject All - Reject all remaining commands");
        $this->line("");
        
        $prompt = "Your choice [N]: ";
        $response = strtolower(trim($this->promptUserInput($prompt, 'n')));
        
        if (in_array($response, ['yes', 'y', '1', 'true'], true)) {
            return 'yes';
        } elseif (in_array($response, ['all', 'a'], true)) {
            return 'all';
        } elseif (in_array($response, ['reject', 'reject-all', 'r'], true)) {
            return 'reject-all';
        }
        
        return 'no';
    }

    private function promptTrustSource(string $registryName): bool
    {
        $this->line("");
        $prompt = "Do you want to trust '{$registryName}' for future installations?\nThis will skip confirmation prompts for PostInstall commands from this source.\n[y]es/[n]o [n]: ";
        $response = strtolower(trim($this->promptUserInput($prompt, 'n')));
        return in_array($response, ['yes', 'y', '1', 'true'], true);
    }

    public function getRegistries(): array
    {
        return $this->registries;
    }

    public function setDebugMode(bool $enabled): void
    {
        $this->debugEnabled = $enabled;
    }

    protected function debug(string $message, string $context = ''): void
    {
        if (!$this->debugEnabled) {
            return;
        }
        $prefix = $context ? "[{$context}] " : '';
        echo "\033[35m{$prefix}{$message}\033[0m\n";
    }

    public function installFromLock(): void
    {
        $lockData = $this->readForgeLockJson();

        if (!isset($lockData['modules']) || !is_array($lockData['modules'])) {
            $this->error("Invalid or empty forge-lock.json module section.");
            return;
        }

        $modulesToInstall = $lockData['modules'];
        $installErrors = false;

        $this->info("Installing modules from forge-lock.json...");

        foreach ($modulesToInstall as $moduleName => $moduleLockInfo) {
            $versionToInstall = $moduleLockInfo['version'] ?? null;
            $expectedIntegrity = $moduleLockInfo['integrity'] ?? null;
            $registryName = $moduleLockInfo['registry'] ?? null;
            $sourceType = $moduleLockInfo['source_type'] ?? 'git';
            $sourceConfig = $moduleLockInfo['source_config'] ?? [];
            $modulePath = $moduleLockInfo['module_path'] ?? null;

            if (!$versionToInstall || !$expectedIntegrity || !$modulePath) {
                $this->error("Incomplete lock information for module '{$moduleName}'. Skipping.");
                $installErrors = true;
                continue;
            }

            $moduleInstallFolderName = $this->generateModuleInstallFolderName($moduleName);
            $moduleCacheFileName = $moduleInstallFolderName . '-' . $versionToInstall . '.zip';
            $moduleCachePath = $this->getCachePath() . $moduleCacheFileName;
            $moduleInstallPath = $this->getModulesPath() . $moduleInstallFolderName;

            $this->info("Installing module {$moduleName} version {$versionToInstall} from lock file...");

            if ($moduleName === self::FRAMEWORK_MODULE_NAME) {
                $this->installFrameworkModule($versionToInstall);
                continue;
            }

            $registryDetails = $registryName ? $this->getRegistryByName($registryName) : null;
            if (!$registryDetails) {
                $this->error("Registry '{$registryName}' not found in configured registries for module '{$moduleName}'. Skipping.");
                $installErrors = true;
                continue;
            }
            $sourceConfig = array_merge($registryDetails, $sourceConfig);
            $sourceConfig['type'] = $sourceType;
            $sourceConfig['debug'] = $this->debugEnabled;
            $source = SourceFactory::create($sourceConfig);

            $this->info("Verifying integrity of {$moduleName}...");
            if (file_exists($moduleCachePath)) {
                $calculatedIntegrity = hash_file('sha256', $moduleCachePath);
                if ($calculatedIntegrity !== $expectedIntegrity) {
                    $this->warning("Integrity mismatch for cached module {$moduleName}. Re-downloading.");
                    unlink($moduleCachePath);
                } else {
                    $this->info("Integrity verified for cached module {$moduleName}.");
                }
            }

            if (!file_exists($moduleCachePath)) {
                $this->info("Downloading module {$moduleName}...");
                $integrityHash = $source->downloadModule($modulePath, $moduleCachePath, $versionToInstall);
                if (!$integrityHash) {
                    $this->error("Failed to download module {$moduleName} from source.");
                    $installErrors = true;
                    continue;
                }

                if ($integrityHash !== $expectedIntegrity) {
                    $this->error("Integrity verification failed after download for module {$moduleName}!");
                    $this->error("Expected integrity: {$expectedIntegrity}");
                    $this->error("Calculated integrity: {$integrityHash}");
                    unlink($moduleCachePath);
                    $installErrors = true;
                    continue;
                }
                $this->info("Integrity verified after download for module {$moduleName}.");
            }

            $this->info("Extracting module {$moduleName}...");
            $extractionSourcePath = '';
            if (!$this->extractModule($moduleCachePath, $moduleInstallPath, $extractionSourcePath)) {
                $this->error("Failed to extract module {$moduleName}.");
                $installErrors = true;
                continue;
            }

            $this->updateForgeJson($moduleName, $versionToInstall);
            $executedCommands = $this->runPostInstallAttributes($moduleInstallPath, $this->toPascalCase($moduleName), $registryName);

            $moduleForgeJsonPath = $moduleInstallPath . '/forge.json';
            if (file_exists($moduleForgeJsonPath)) {
                $moduleForgeJsonContent = file_get_contents($moduleForgeJsonPath);
                $moduleConfig = json_decode($moduleForgeJsonContent, true);

                if (isset($moduleConfig['postInstall']['commands']) && is_array($moduleConfig['postInstall']['commands'])) {
                    $commandCount = count($moduleConfig['postInstall']['commands']);
                    $isTrusted = $this->isSourceTrusted($registryName);
                    
                    if (!$isTrusted) {
                        $this->showPostInstallWarning($moduleName, $registryName, $commandCount);
                    } else {
                        $this->info("Source '{$registryName}' is trusted. Executing all PostInstall commands automatically.");
                    }

                    $acceptAll = false;
                    $rejectAll = false;

                    foreach ($moduleConfig['postInstall']['commands'] as $index => $command) {
                        if ($rejectAll) {
                            $this->info("Skipping command " . ($index + 1) . " of {$commandCount} (rejected all).");
                            continue;
                        }

                        if ($isTrusted || $acceptAll) {
                            $shouldExecute = true;
                        } else {
                            $response = $this->confirmPostInstallCommand($command, $moduleName, $registryName, $index + 1, $commandCount);
                            
                            if ($response === 'reject-all') {
                                $rejectAll = true;
                                $shouldExecute = false;
                            } elseif ($response === 'all') {
                                $acceptAll = true;
                                $shouldExecute = true;
                            } else {
                                $shouldExecute = ($response === 'yes');
                            }
                        }

                        if ($shouldExecute) {
                            $this->info("Executing command: {$command}");
                            exec($command, $output, $returnVar);

                            if ($returnVar !== 0) {
                                $this->error("Post-install command '{$command}' failed for module {$moduleName} with exit code: {$returnVar}");
                                if (!empty($output)) {
                                    $this->error("Command output:\n" . implode("\n", $output));
                                }
                                $this->warning("Post-install command failure - Module installation continues with warnings.");
                            } else {
                                $this->info("Post-install command '{$command}' executed successfully for module {$moduleName}.");
                                if (!empty($output)) {
                                    $this->info("Command output:\n" . implode("\n", $output));
                                }
                                $executedCommands[] = $command;
                            }
                        } else {
                            $this->info("Skipping command: {$command}");
                        }
                    }
                }
            }

            if (!empty($executedCommands) && !$this->isSourceTrusted($registryName)) {
                if ($this->promptTrustSource($registryName)) {
                    $this->trustSource($registryName);
                    $this->success("Source '{$registryName}' has been trusted for future installations.");
                }
            }

            $this->success("Module {$moduleName} version {$versionToInstall} installed from lock file successfully.");
        }

        if ($installErrors) {
            $this->error("Some modules failed to install from forge-lock.json. Check error messages above.");
        } else {
            $this->success("All modules from forge-lock.json installed successfully.");
        }
    }

    private function readForgeLockJson(): array
    {
        $forgeLockJsonPath = BASE_PATH . '/forge-lock.json';
        if (!file_exists($forgeLockJsonPath)) {
            $defaultLockData = ['modules' => []];
            file_put_contents($forgeLockJsonPath, json_encode($defaultLockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $defaultLockData;
        }
        $content = file_get_contents($forgeLockJsonPath);
        return json_decode($content, true) ?? ['modules' => []];
    }

    private function getRegistryByName(string $name): ?array
    {
        foreach ($this->registries as $registry) {
            if ($registry['name'] === $name) {
                return $registry;
            }
        }
        return null;
    }

    private function generateModuleInstallFolderName(string $fullName): string
    {
        return Strings::toPascalCase($fullName);
    }

    private function getCachePath(): string
    {
        return $this->cachePath;
    }

    private function getModulesPath(): string
    {
        return $this->modulesPath;
    }

    private function installFrameworkModule(?string $version = null): void
    {
        $this->info("Installing Forge Engine Framework...");

        $installScriptPath = BASE_PATH . '/install.php';
        if (!file_exists($installScriptPath)) {
            $this->error("Error: install.php script not found in project root.");
            return;
        }

        $command = "php " . escapeshellarg($installScriptPath);
        if ($version) {
            $command .= " --version=" . escapeshellarg($version);
        }

        $this->info("Executing framework install script: {$command}");

        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (is_resource($process)) {
            fclose($pipes[0]);

            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);

            echo $stdout;

            if ($returnCode !== 0) {
                $this->error("Framework install script failed with exit code: {$returnCode}");
                if (!empty($stderr)) {
                    $this->error("Install script error output:\n" . $stderr);
                }
            } else {
                $this->success("Forge Framework installed successfully.");
                if (!empty($stderr)) {
                    $this->warning("Framework install script had warnings:\n" . $stderr);
                }
                $this->updateForgeJson(self::FRAMEWORK_MODULE_NAME, $version ?: 'latest');
            }
        } else {
            $this->error("Failed to execute framework install script.");
        }
    }

    private function updateForgeJson(string $moduleName, string $version): void
    {
        $forgeJsonPath = BASE_PATH . '/forge.json';
        $forgeConfig = $this->readForgeJson();

        $forgeConfig['modules'][$moduleName] = $version;
        $this->writeForgeJson($forgeConfig);
    }

    private function readForgeJson(): array
    {
        $forgeJsonPath = BASE_PATH . '/forge.json';
        if (!file_exists($forgeJsonPath)) {
            $defaultConfig = [
                'name' => 'Forge Framework',
                'engine' => [
                    'name' => 'forge-engine',
                    'version' => 'latest'
                ],
                'modules' => [

                ],
            ];
            file_put_contents($forgeJsonPath, json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $defaultConfig;
        }
        $content = file_get_contents($forgeJsonPath);
        return json_decode($content, true) ?? ['modules' => []];
    }

    private function writeForgeJson(array $data): void
    {
        $forgeJsonPath = BASE_PATH . '/forge.json';
        file_put_contents($forgeJsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }


    private function extractModule(string $zipPath, string $destinationPath, string $sourcePathInZip): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $this->removeDirectory($destinationPath);
            if (!mkdir($destinationPath, 0755, true) && !is_dir($destinationPath)) {
                $this->error("Failed to create module directory: {$destinationPath}");
                return false;
            }

            $zip->extractTo($destinationPath);

            $zip->close();
            return true;
        } else {
            return false;
        }
    }

    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * @throws ReflectionException
     */
    private function runPostInstallAttributes(string $moduleInstallPath, string $moduleName, string $registryName): array
    {
        $executedCommands = [];
        $moduleSrc = glob($moduleInstallPath . '/**/*.php');
        if (!$moduleSrc) {
            $this->warning("No PHP files found in module {$moduleName}, skipping PostInstall scanning.");
            return $executedCommands;
        }

        foreach ($moduleSrc as $file) {
            require_once $file;
        }

        $foundModuleClass = false;

        foreach (get_declared_classes() as $class) {
            $ref = new ReflectionClass($class);
            $moduleAttr = $ref->getAttributes(Module::class);

            if (empty($moduleAttr)) {
                continue;
            }

            $moduleInstance = $moduleAttr[0]->newInstance();

            if ($moduleInstance->name === $moduleName) {
                $foundModuleClass = true;
                $postInstallAttrs = $ref->getAttributes(PostInstall::class);

                if (empty($postInstallAttrs)) {
                    $this->info("Module {$moduleName} has no PostInstall attributes defined.");
                    return $executedCommands;
                }

                $commandCount = count($postInstallAttrs);
                $isTrusted = $this->isSourceTrusted($registryName);
                
                if (!$isTrusted) {
                    $this->showPostInstallWarning($moduleName, $registryName, $commandCount);
                } else {
                    $this->info("Source '{$registryName}' is trusted. Executing all PostInstall commands automatically.");
                }

                $acceptAll = false;
                $rejectAll = false;

                foreach ($postInstallAttrs as $index => $attr) {
                    if ($rejectAll) {
                        $this->info("Skipping command " . ($index + 1) . " of {$commandCount} (rejected all).");
                        continue;
                    }

                    /** @var PostInstall $instance */
                    $instance = $attr->newInstance();
                    $args = implode(' ', $instance->args);
                    $command = "php forge.php {$instance->command} {$args}";

                    if ($isTrusted || $acceptAll) {
                        $shouldExecute = true;
                    } else {
                        $response = $this->confirmPostInstallCommand($command, $moduleName, $registryName, $index + 1, $commandCount);
                        
                        if ($response === 'reject-all') {
                            $rejectAll = true;
                            $shouldExecute = false;
                        } elseif ($response === 'all') {
                            $acceptAll = true;
                            $shouldExecute = true;
                        } else {
                            $shouldExecute = ($response === 'yes');
                        }
                    }

                    if ($shouldExecute) {
                        $this->info("Running: {$command}");
                        exec($command, $output, $code);
                        $this->line();

                        if ($code !== 0) {
                            $this->error("Command '{$command}' failed for module {$moduleName} (exit code {$code})");
                            if (!empty($output)) {
                                $this->error("Output:\n" . implode("\n", $output));
                            }
                        } else {
                            $this->success("Command '{$command}' executed successfully.");
                            $executedCommands[] = $command;
                        }
                    } else {
                        $this->info("Skipping command: {$command}");
                    }
                }

                return $executedCommands;
            }
        }

        if (!$foundModuleClass) {
            $this->warning("No #[Module] class found for '{$moduleName}', skipping PostInstall execution.");
        }

        return $executedCommands;
    }

    /**
     * @throws ReflectionException
     */
    public function installModule(string $moduleName, ?string $version = null, ?string $forceCache = null): void
    {
        $this->info("Installing module: {$moduleName}" . ($version ? " version {$version}" : " (latest)"));

        if ($moduleName === self::FRAMEWORK_MODULE_NAME) {
            $this->installFrameworkModule($version);
            return;
        }

        $moduleInfo = $this->getModuleInfo($moduleName, $version);
        if (!$moduleInfo) {
            $this->error("Module '{$moduleName}' not found in registries.");
            return;
        }

        $versionToInstall = $version ?? (isset($moduleInfo['latest']) ? $moduleInfo['latest'] : null);
        $versionDetails = isset($moduleInfo['versions'][$versionToInstall]) ? $moduleInfo['versions'][$versionToInstall] : null;

        if (!$versionDetails) {
            $this->error("Version '{$versionToInstall}' for module '{$moduleName}' version '{$versionToInstall}' not found.");
            return;
        }

        $moduleDownloadPathInRepo = $versionDetails['url'];
        $registryDetails = $this->getRegistryDetailsForModule($moduleName);
        if (!$registryDetails) {
            $this->error("No registry found for module '{$moduleName}'. Please ensure registries are configured in config/source_list.php");
            return;
        }
        $sourceType = $registryDetails['type'] ?? 'git';
        $sourceConfig = $registryDetails;
        $sourceConfig['type'] = $sourceType;
        $sourceConfig['debug'] = $this->debugEnabled;
        $source = SourceFactory::create($sourceConfig);
        
        $moduleInstallFolderName = $this->generateModuleInstallFolderName($moduleName);
        $moduleCacheFileName = $moduleInstallFolderName . '-' . $versionToInstall . '.zip';
        $moduleCachePath = $this->getCachePath() . $moduleCacheFileName;
        $moduleInstallPath = $this->getModulesPath() . $moduleInstallFolderName;

        if ($forceCache === 'force') {
            if (file_exists($moduleCachePath)) {
                unlink($moduleCachePath);
                $this->info("Cache bypassed, deleted cached module {$moduleName} version {$versionToInstall}.");
            }
            $this->info("Downloading module {$moduleName} version {$versionToInstall} from remote...");
            $integrityHash = $source->downloadModule($moduleDownloadPathInRepo, $moduleCachePath, $versionToInstall);
            $this->integrityHash = $integrityHash;
            if (!$integrityHash) {
                $this->error("Failed to download module {$moduleName}.");
                return;
            }
        } elseif (!file_exists($moduleCachePath)) {
            $this->info("Downloading module {$moduleName} version {$versionToInstall}...");
            $integrityHash = $source->downloadModule($moduleDownloadPathInRepo, $moduleCachePath, $versionToInstall);
            $this->integrityHash = $integrityHash;
            if (!$integrityHash) {
                $this->error("Failed to download module {$moduleName}.");
                return;
            }
        } else {
            $this->info("Using cached module {$moduleName} version {$versionToInstall}.");
            $integrityHash = hash_file('sha256', $moduleCachePath);
            if (!$integrityHash) {
                $this->error("Failed to calculate integrity hash for cached module {$moduleName}.");
                return;
            }
        }

        $extractionSourcePath = '';
        if (!$this->extractModule($moduleCachePath, $moduleInstallPath, $extractionSourcePath)) {
            $this->error("Failed to extract module {$moduleName}.");
            return;
        }

        $this->updateForgeJson($moduleName, $versionToInstall);
        $this->createForgeLockJson($moduleName, $versionToInstall, $registryDetails, $moduleDownloadPathInRepo, $integrityHash, $sourceType);
        
        $registryName = $registryDetails['name'] ?? 'unknown';
        $executedCommands = $this->runPostInstallAttributes($moduleInstallPath, $this->toPascalCase($moduleName), $registryName);

        $moduleForgeJsonPath = $moduleInstallPath . '/forge.json';
        if (file_exists($moduleForgeJsonPath)) {
            $moduleForgeJsonContent = file_get_contents($moduleForgeJsonPath);
            $moduleConfig = json_decode($moduleForgeJsonContent, true);

            if (isset($moduleConfig['postInstall']['commands']) && is_array($moduleConfig['postInstall']['commands'])) {
                $commandCount = count($moduleConfig['postInstall']['commands']);
                $isTrusted = $this->isSourceTrusted($registryName);
                
                if (!$isTrusted) {
                    $this->showPostInstallWarning($moduleName, $registryName, $commandCount);
                } else {
                    $this->info("Source '{$registryName}' is trusted. Executing all PostInstall commands automatically.");
                }

                $acceptAll = false;
                $rejectAll = false;

                foreach ($moduleConfig['postInstall']['commands'] as $index => $command) {
                    if ($rejectAll) {
                        $this->info("Skipping command " . ($index + 1) . " of {$commandCount} (rejected all).");
                        continue;
                    }

                    if ($isTrusted || $acceptAll) {
                        $shouldExecute = true;
                    } else {
                        $response = $this->confirmPostInstallCommand($command, $moduleName, $registryName, $index + 1, $commandCount);
                        
                        if ($response === 'reject-all') {
                            $rejectAll = true;
                            $shouldExecute = false;
                        } elseif ($response === 'all') {
                            $acceptAll = true;
                            $shouldExecute = true;
                        } else {
                            $shouldExecute = ($response === 'yes');
                        }
                    }

                    if ($shouldExecute) {
                        $this->info("Executing command: {$command}");
                        exec($command, $output, $returnVar);

                        if ($returnVar !== 0) {
                            $this->error("Post-install command '{$command}' failed for module {$moduleName} with exit code: {$returnVar}");
                            if (!empty($output)) {
                                $this->error("Command output:\n" . implode("\n", $output));
                            }
                            $this->warning("Post-install command failure - Module installation continues with warnings.");
                        } else {
                            $this->info("Post-install command '{$command}' executed successfully for module {$moduleName}.");
                            if (!empty($output)) {
                                $this->info("Command output:\n" . implode("\n", $output));
                            }
                            $executedCommands[] = $command;
                        }
                    } else {
                        $this->info("Skipping command: {$command}");
                    }
                }
            }
        }

        if (!empty($executedCommands) && !$this->isSourceTrusted($registryName)) {
            if ($this->promptTrustSource($registryName)) {
                $this->trustSource($registryName);
                $this->success("Source '{$registryName}' has been trusted for future installations.");
            }
        }

        // Only show success message if we reached this point without early returns
        // This means: module info was found, download succeeded, extraction succeeded
        $this->success("Module {$moduleName} version {$versionToInstall} installed successfully.");
    }

    private function getModulesDataForRegistry(string $registryName, string $sourceType, array $registryConfig, SourceInterface $source): ?array
    {
        $debugConfig = $registryConfig;
        if (isset($debugConfig['personal_token'])) {
            $debugConfig['personal_token'] = '***hidden***';
        }
        if (isset($debugConfig['password'])) {
            $debugConfig['password'] = '***hidden***';
        }
        
        $this->debug("Checking registry: {$registryName}");
        $this->debug("Registry config: " . json_encode($debugConfig, JSON_UNESCAPED_SLASHES));
        $this->debug("Source type: {$sourceType}");
        
        $cacheKey = md5($registryName . $sourceType . serialize($registryConfig));
        $cacheFile = $this->getCachePath() . $cacheKey . '.cache';
        $this->debug("Cache file: {$cacheFile}");
        $modulesData = null;

        if (file_exists($cacheFile)) {
            $age = time() - filemtime($cacheFile);
            if ($age >= $this->cacheTtl) {
                unlink($cacheFile);
                $this->info("Cache expired, cleared cache file for {$registryName}.");
            } else {
                $this->info("Using cached module list from {$registryName}.");
                $modulesData = json_decode(file_get_contents($cacheFile), true);
            }
        }

        if (!is_array($modulesData)) {
            $this->info("Fetching module list from {$registryName}...");
            $modulesData = $source->fetchModulesJson();
            
            if ($modulesData === null || !is_array($modulesData)) {
                $this->warning("Failed to fetch module list from registry: {$registryName}");
                return null;
            }

            $written = @file_put_contents($cacheFile, json_encode($modulesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            if ($written === false) {
                $this->warning("Failed to write cache file for registry: {$registryName}");
            } else {
                $this->debug("Cache file written successfully: {$cacheFile}");
            }
        }

        return $modulesData;
    }

    public function getModuleInfo(?string $moduleName = null, ?string $version = null): ?array
    {
        if (empty($this->registries)) {
            $this->error("No package registries configured. Please configure registries in config/source_list.php");
            return null;
        }

        if (!$moduleName) {
            return null;
        }

        $this->debug("Searching for module: {$moduleName}");
        $this->debug("Checking " . count($this->registries) . " registry(ies)");

        foreach ($this->registries as $index => $registryConfig) {
            $sourceType = $registryConfig['type'] ?? 'git';
            $registryConfig['debug'] = $this->debugEnabled;
            $source = SourceFactory::create($registryConfig);
            $registryName = $registryConfig['name'] ?? 'unknown';
            
            $this->debug("Registry " . ($index + 1) . "/" . count($this->registries) . ": {$registryName}");
            
            $modulesData = $this->getModulesDataForRegistry($registryName, $sourceType, $registryConfig, $source);
            
            if ($modulesData && isset($modulesData[$moduleName])) {
                $this->debug("Module '{$moduleName}' found in registry: {$registryName}");
                return $modulesData[$moduleName];
            } else {
                $this->debug("Module '{$moduleName}' not found in registry: {$registryName}");
            }
        }

        $this->error("Module '{$moduleName}' not found in any configured registry.");
        return null;
    }

    private function getRegistryDetailsForModule(?string $moduleName): ?array
    {
        if (empty($this->registries)) {
            return null;
        }

        if ($moduleName) {
            foreach ($this->registries as $registry) {
                $sourceType = $registry['type'] ?? 'git';
                $registry['debug'] = $this->debugEnabled;
                $source = SourceFactory::create($registry);
                $registryName = $registry['name'] ?? 'unknown';
                
                $modulesData = $this->getModulesDataForRegistry($registryName, $sourceType, $registry, $source);
                
                if ($modulesData && isset($modulesData[$moduleName])) {
                    return $registry;
                }
            }
        }

        return $this->registries[0] ?? null;
    }

    private function createForgeLockJson(string $moduleName, string $version, array $registryDetails, string $modulePath, string $integrityHash, string $sourceType): void
    {
        $forgeLockJsonPath = BASE_PATH . '/forge-lock.json';
        $lockData = $this->readForgeLockJson();

        $lockData['modules'][$moduleName] = [
            'version' => $version,
            'registry' => $registryDetails['name'] ?? 'unknown',
            'module_path' => $modulePath,
            'integrity' => $integrityHash,
            'source_type' => $sourceType,
            'source_config' => $registryDetails,
        ];

        $this->writeForgeLockJson($lockData);
    }

    private function writeForgeLockJson(array $data): void
    {
        $forgeLockJsonPath = BASE_PATH . '/forge-lock.json';
        file_put_contents($forgeLockJsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function removeModule(string $moduleName): void
    {
        if ($moduleName === self::PACKAGE_MANAGER_MODULE_NAME) {
            $this->warning("Uninstalling 'forge-package-manager' will disable automatic module management.");
            $this->warning("You will need to manually download and install modules until another package manager is installed.");
            $this->warning("Consider installing another package manager or reinstalling forge-package-manager afterwards.");
        }

        $moduleInstallFolderName = $this->generateModuleInstallFolderName($moduleName);
        $moduleInstallPath = $this->getModulesPath() . $moduleInstallFolderName;

        if (!is_dir($moduleInstallPath)) {
            $this->info("Cleaning stale module entry: {$moduleName}");
            $this->updateForgeJsonOnModuleRemoval($moduleName);
            $this->updateForgeLockJsonOnModuleRemoval($moduleName);
            $this->success("Stale module entry '{$moduleName}' cleaned successfully.");
            return;
        }

        try {
            $this->runPostUninstallAttributes($moduleInstallPath, $this->toPascalCase($moduleName));
        } catch (\ReflectionException $e) {
            $this->warning("Failed to execute PostUninstall for {$moduleName}: " . $e->getMessage());
        }

        $this->info("Removing module {$moduleName}...");

        if (!$this->removeDirectory($moduleInstallPath)) {
            $this->error("Failed to delete module directory: {$moduleInstallPath}");
            return;
        }

        $this->updateForgeJsonOnModuleRemoval($moduleName);
        $this->updateForgeLockJsonOnModuleRemoval($moduleName);

        $this->success("Module {$moduleName} removed successfully.");
    }

    /**
     * Executes #[PostUninstall] commands defined in the module class before removal.
     *
     * @throws ReflectionException
     */
    private function runPostUninstallAttributes(string $moduleInstallPath, string $moduleName): void
    {
        $moduleSrc = glob($moduleInstallPath . '/**/*.php');
        if (!$moduleSrc) {
            $this->warning("No PHP files found in module {$moduleName}, skipping PostUninstall scanning.");
            return;
        }

        foreach ($moduleSrc as $file) {
            require_once $file;
        }

        $foundModuleClass = false;

        foreach (get_declared_classes() as $class) {
            $ref = new ReflectionClass($class);
            $moduleAttr = $ref->getAttributes(Module::class);

            if (empty($moduleAttr)) {
                continue;
            }

            $moduleInstance = $moduleAttr[0]->newInstance();

            if ($moduleInstance->name === $moduleName) {
                $foundModuleClass = true;
                $postUninstallAttrs = $ref->getAttributes(PostUninstall::class);

                if (empty($postUninstallAttrs)) {
                    $this->info("Module {$moduleName} has no PostUninstall attributes defined.");
                    return;
                }

                $this->info("Executing PostUninstall commands for module {$moduleName}...");

                foreach ($postUninstallAttrs as $attr) {
                    /** @var PostUninstall $instance */
                    $instance = $attr->newInstance();
                    $args = implode(' ', $instance->args);
                    $command = "php forge.php {$instance->command} {$args}";
                    $this->info("Running: {$command}");

                    exec($command, $output, $code);
                    $this->line();

                    if ($code !== 0) {
                        $this->error("Command '{$command}' failed for module {$moduleName} (exit code {$code})");
                        if (!empty($output)) {
                            $this->error("Output:\n" . implode("\n", $output));
                        }
                    } else {
                        $this->success("Command '{$command}' executed successfully.");
                    }
                }

                return;
            }
        }

        if (!$foundModuleClass) {
            $this->warning("No #[Module] class found for '{$moduleName}', skipping PostUninstall execution.");
        }
    }
    
    private function updateForgeJsonOnModuleRemoval(string $moduleName): void
    {
        $forgeJsonPath = BASE_PATH . '/forge.json';
        $forgeConfig = $this->readForgeJson();
        if (isset($forgeConfig['modules'][$moduleName])) {
            unset($forgeConfig['modules'][$moduleName]);
            $this->writeForgeJson($forgeConfig);
            $this->info("Removed '{$moduleName}' from forge.json.");
        } else {
            $this->warning("Module '{$moduleName}' not found in forge.json modules section. Skipping forge.json update.");
        }
    }

    private function updateForgeLockJsonOnModuleRemoval(string $moduleName): void
    {
        $forgeLockJsonPath = BASE_PATH . '/forge-lock.json';
        $lockData = $this->readForgeLockJson();
        if (isset($lockData['modules'][$moduleName])) {
            unset($lockData['modules'][$moduleName]);
            $this->writeForgeLockJson($lockData);
            $this->info("Removed '{$moduleName}' from forge-lock.json.");
        } else {
            $this->warning("Module '{$moduleName}' not found in forge-lock.json modules section. Skipping forge-lock.json update.");
        }
    }

    public function moduleHasMigrations(string $module): bool
    {
        return is_dir(BASE_PATH . "/modules/{$module}/src/Database/Migrations");
    }

    public function moduleHasSeeders(string $module): bool
    {
        return is_dir(BASE_PATH . "/modules/{$module}/src/Database/Seeders");
    }

    public function moduleHasAssets(string $module): bool
    {
        return is_dir(BASE_PATH . "/modules/{$module}/src/Resources/assets") ||
            is_dir(BASE_PATH . "/public/modules/{$module}");
    }
}

