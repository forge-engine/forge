<?php

declare(strict_types=1);

namespace App\Modules\ForgePackageManager\Services;

use App\Modules\ForgePackageManager\Contracts\PackageManagerInterface;
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
use ReflectionException;
use ZipArchive;

#[Service]
#[Provides(interface: PackageManagerInterface::class, version: '0.1.0')]
#[Requires()]
final class PackageManagerService implements PackageManagerInterface
{
    use OutputHelper;
    use StringHelper;

    private const string OFFICIAL_REGISTRY_NAME = 'forge-engine-modules';
    private const string OFFICIAL_REGISTRY_BASE_URL = 'https://github.com/forge-engine/modules';
    private const string OFFICIAL_REGISTRY_BRANCH = 'main';
    private const string FRAMEWORK_MODULE_NAME = 'forge-engine/framework';
    private const string PACKAGE_MANAGER_MODULE_NAME = 'forge-package-manager';

    private array $registries = [];
    private int $cacheTtl;
    private string $modulesPath;
    private string $cachePath;
    private string $integrityHash;

    public function __construct(private readonly Config $config)
    {
        $this->registries = $this->config->get('forge_package_manager.registry', []);
        $this->cacheTtl = $this->config->get('forge_package_manager.cache_ttl', 3600);
        $this->modulesPath = BASE_PATH . '/modules/';
        $this->cachePath = BASE_PATH . '/storage/framework/cache/modules/';

        $this->ensureCacheDirectoryExists();
        $this->ensureModulesDirectoryExists();
    }

    public function getRegistries(): array
    {
        return $this->registries;
    }

    public function getDefaultRegistryDetails(): array
    {
        return [
            'name' => self::OFFICIAL_REGISTRY_NAME,
            'url' => self::OFFICIAL_REGISTRY_BASE_URL,
            'branch' => self::OFFICIAL_REGISTRY_BRANCH
        ];
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
            $downloadUrl = $moduleLockInfo['url'] ?? null;
            $expectedIntegrity = $moduleLockInfo['integrity'] ?? null;
            $registryName = $moduleLockInfo['registry'] ?? self::OFFICIAL_REGISTRY_NAME;
            $registryDetails = $this->getRegistryByName($registryName);
            $token = $registryDetails['personal_token'] ?? null;

            if (!$versionToInstall || !$downloadUrl || !$expectedIntegrity) {
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
                $this->info("Downloading module {$moduleName} from {$downloadUrl}...");
                $integrityHash = $this->downloadFile($downloadUrl, $moduleCachePath, $token);
                if (!$integrityHash) {
                    $this->error("Failed to download module {$moduleName} from URL in lock file: {$downloadUrl}");
                    $installErrors = true;
                    continue;
                }

                if ($integrityHash !== $expectedIntegrity) {
                    $this->error("Integrity verification failed after download for module {$moduleName} from {$downloadUrl}!");
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

            $this->success("Module {$moduleName} version {$versionToInstall} installed from lock file successfully.");
        }

        if ($installErrors) {
            $this->error("Some modules failed to install from forge-lock.json. Check error messages above.");
        } else {
            $this->success("All modules from forge-lock.json installed successfully.");
        }
    }

    private function getRegistryByName(string $name): array
    {
        foreach ($this->registries as $registry) {
            if ($registry['name'] === $name) {
                return $registry;
            }
        }
        return $this->getDefaultRegistryDetails();
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
        $token = $registryDetails['personal_token'] ?? null;
        $registryRawBaseUrl = $this->getRegistryRawBaseUrl($registryDetails);
        $moduleInstallFolderName = $this->generateModuleInstallFolderName($moduleName);
        $moduleCacheFileName = $moduleInstallFolderName . '-' . $versionToInstall . '.zip';
        $moduleCachePath = $this->getCachePath() . $moduleCacheFileName;
        $moduleInstallPath = $this->getModulesPath() . $moduleInstallFolderName;

        $githubZipUrl = $this->generateGithubZipUrl($registryRawBaseUrl, $registryDetails['branch'], $moduleDownloadPathInRepo);

        if ($forceCache === 'force' || !file_exists($moduleCachePath)) {
            $this->info("Downloading module {$moduleName} version {$versionToInstall} from {$githubZipUrl}...");
            $integrityHash = $this->downloadFile($githubZipUrl, $moduleCachePath, $token);
            $this->integrityHash = $integrityHash;
            if (!$integrityHash) {
                $this->error("Failed to download module {$moduleName} from GitHub.");
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
        $this->createForgeLockJson($moduleName, $versionToInstall, $registryDetails, $githubZipUrl, $integrityHash);
        $this->runPostInstallAttributes($moduleInstallPath, $this->toPascalCase($moduleName));

        $moduleForgeJsonPath = $moduleInstallPath . '/forge.json';
        if (file_exists($moduleForgeJsonPath)) {
            $moduleForgeJsonContent = file_get_contents($moduleForgeJsonPath);
            $moduleConfig = json_decode($moduleForgeJsonContent, true);

            if (isset($moduleConfig['postInstall']['commands']) && is_array($moduleConfig['postInstall']['commands'])) {
                $this->info("Executing post-install commands for module {$moduleName}...");
                foreach ($moduleConfig['postInstall']['commands'] as $command) {
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
                    }
                }
            }
        }
        $this->success("Module {$moduleName} version {$versionToInstall} installed successfully.");
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

    private function updateForgeJson(string $moduleName, string $version): void
    {
        $forgeJsonPath = BASE_PATH . '/forge.json';
        $forgeConfig = $this->readForgeJson();

        // Ensure framework is always present
        if (!isset($forgeConfig['modules']['forge-engine/framework'])) {
            $forgeConfig['modules']['forge-engine/framework'] = 'latest';
        }

        $forgeConfig['modules'][$moduleName] = $version;
        $this->writeForgeJson($forgeConfig);
    }

    private function createForgeLockJson(string $moduleName, string $version, array $registryDetails, string $downloadUrl, string $integrityHash): void
    {
        $forgeLockJsonPath = BASE_PATH . '/forge-lock.json';
        $lockData = $this->readForgeLockJson();

        $lockData['modules'][$moduleName] = [
            'version' => $version,
            'registry' => $registryDetails['name'] ?? self::OFFICIAL_REGISTRY_NAME,
            'url' => $downloadUrl,
            'integrity' => $integrityHash,
        ];

        $this->writeForgeLockJson($lockData);
    }

    private function getCachePath(): string
    {
        return $this->cachePath;
    }

    private function getModulesPath(): string
    {
        return $this->modulesPath;
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

    private function writeForgeLockJson(array $data): void
    {
        $forgeLockJsonPath = BASE_PATH . '/forge-lock.json';
        file_put_contents($forgeLockJsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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

    private function generateModuleInstallFolderName(string $fullName): string
    {
        return Strings::toPascalCase($fullName);
    }

    public function removeModule(string $moduleName): void
    {
        if ($moduleName === self::FRAMEWORK_MODULE_NAME) {
            $this->warning("Uninstalling 'forge-engine/framework' may cause critical system errors.");
            $this->warning("Only proceed if you understand the risks. Most functionality will be disabled.");
            $this->warning("It's highly recommended to reinstall the framework afterwards to restore functionality.");
        }

        if ($moduleName === self::PACKAGE_MANAGER_MODULE_NAME) {
            $this->warning("Uninstalling 'forge-package-manager' will disable automatic module management.");
            $this->warning("You will need to manually download and install modules until another package manager is installed.");
            $this->warning("Consider installing another package manager or reinstalling forge-package-manager afterwards.");
        }

        $moduleInstallFolderName = $this->generateModuleInstallFolderName($moduleName);
        $moduleInstallPath = $this->getModulesPath() . $moduleInstallFolderName;

        if (!is_dir($moduleInstallPath)) {
            $this->warning("Module '{$moduleName}' is not currently installed, or the installation folder is missing: {$moduleInstallPath}");
            $this->warning("Skipping module removal.");
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

    public function getModuleInfo(?string $moduleName = null, ?string $version = null): ?array
    {
        $registryDetails = $this->getRegistryDetailsForModule($moduleName);
        $modulesJsonUrl = $this->getModulesJsonUrl($registryDetails);
        $token = $registryDetails['personal_token'] ?? null;

        $cacheKey = md5($modulesJsonUrl);
        $cacheFile = $this->getCachePath() . $cacheKey . '.cache';
        $modulesData = null;

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $this->cacheTtl)) {
            $this->info("Using cached module list from " . (isset($registryDetails['name']) ? $registryDetails['name'] : $modulesJsonUrl) . ".");
            $modulesData = json_decode(file_get_contents($cacheFile), true);
        }

        $context = null;
        if ($token) {
            $context = stream_context_create([
                'http' => [
                    'header' => "Authorization: token $token\r\n"
                ]
            ]);
        }

        if (!is_array($modulesData) || !isset($modulesData[$moduleName])) {
            $this->info("Fetching module list from " . (isset($registryDetails['name']) ? $registryDetails['name'] : $modulesJsonUrl) . "...");
            $modulesJsonContent = @file_get_contents($modulesJsonUrl, false, $context);

            if ($modulesJsonContent === false) {
                $this->error("Failed to fetch module list from registry: {$modulesJsonUrl}");
                return null;
            }

            $modulesData = json_decode($modulesJsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonError = json_last_error_msg();
                $this->error("JSON decode error: " . $jsonError);
                $this->error("Problematic JSON content (raw):");
                $this->error($modulesJsonContent);
                return null;
            }

            if (!is_array($modulesData)) {
                $this->error("Invalid module list format from registry from " . (isset($registryDetails['name']) ? $registryDetails['name'] : $modulesJsonUrl) . ".");
                return null;
            }
            file_put_contents($cacheFile, $modulesJsonContent);
        }

        return $modulesData[$moduleName] ?? null;
    }

    private function getRegistryDetailsForModule(?string $moduleName): array
    {
        if ($moduleName === null) {
            return [
                'name' => self::OFFICIAL_REGISTRY_NAME,
                'url' => self::OFFICIAL_REGISTRY_BASE_URL,
                'branch' => self::OFFICIAL_REGISTRY_BRANCH,
            ];
        }

        if (strpos($moduleName, 'forge-') === 0 && $moduleName !== self::FRAMEWORK_MODULE_NAME) {
            return [
                'name' => self::OFFICIAL_REGISTRY_NAME,
                'url' => self::OFFICIAL_REGISTRY_BASE_URL,
                'branch' => self::OFFICIAL_REGISTRY_BRANCH,
            ];
        }

        foreach ($this->registries as $registry) {
            return $registry;
        }

        return [
            'name' => self::OFFICIAL_REGISTRY_NAME,
            'url' => self::OFFICIAL_REGISTRY_BASE_URL,
            'branch' => self::OFFICIAL_REGISTRY_BRANCH,
        ];
    }

    private function getModulesJsonUrl(array $registryDetails): string
    {
        $registryRawBaseUrl = $this->getRegistryRawBaseUrl($registryDetails);
        return rtrim($registryRawBaseUrl, '/') . '/modules.json';
    }

    private function getRegistryRawBaseUrl(array $registryDetails): string
    {
        $registryUrl = $registryDetails['url'];
        $branch = $registryDetails['branch'] ?? 'main';

        if (preg_match('/^git@github\.com:(?<user>[^\/]+)\/(?<repo>[^\.]+).git$/', $registryUrl, $matches)) {
            return "https://raw.githubusercontent.com/{$matches['user']}/{$matches['repo']}/{$branch}";
        }

        // Handle HTTPS format
        if (preg_match('#^https?://github\.com/(?<user>[^/]+)/(?<repo>[^/]+)#i', $registryUrl, $matches)) {
            return "https://raw.githubusercontent.com/{$matches['user']}/{$matches['repo']}/{$branch}";
        }

        return $registryUrl;
    }

    private function generateGithubZipUrl(string $registryRawBaseUrl, string $branch, string $modulePathInRepo): string
    {
        $repoBaseRawUrl = rtrim($registryRawBaseUrl, '/');
        $zipPathInRepo = 'modules/' . $modulePathInRepo;

        $versionFolderName = basename($modulePathInRepo);
        $zipFileName = $versionFolderName . '.zip';

        $githubZipUrl = $repoBaseRawUrl . '/' . $zipPathInRepo . '/' . $zipFileName;

        return $githubZipUrl;
    }

    private function downloadFile(string $url, string $destination, ?string $token = null): bool|string
    {
        $context = null;
        if ($token) {
            $context = stream_context_create([
                'http' => [
                    'header' => "Authorization: token $token\r\n"
                ]
            ]);
        }

        $fileContent = @file_get_contents($url, false, $context);
        if ($fileContent === false) {
            return false;
        }
        if (file_put_contents($destination, $fileContent) !== false) {
            $calculatedHash = hash_file('sha256', $destination);
            return $calculatedHash;
        }
        return false;
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

    /**
     * @throws ReflectionException
     */
    /**
     * @throws ReflectionException
     */
    private function runPostInstallAttributes(string $moduleInstallPath, string $moduleName): void
    {
        $moduleSrc = glob($moduleInstallPath . '/**/*.php');
        if (!$moduleSrc) {
            $this->warning("No PHP files found in module {$moduleName}, skipping PostInstall scanning.");
            return;
        }

        foreach ($moduleSrc as $file) {
            require_once $file;
        }

        $foundModuleClass = false;

        foreach (get_declared_classes() as $class) {
            $ref = new \ReflectionClass($class);
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
                    return;
                }

                $this->info("Executing PostInstall commands for module {$moduleName}...");

                foreach ($postInstallAttrs as $attr) {
                    /** @var PostInstall $instance */
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
            $this->warning("No #[Module] class found for '{$moduleName}', skipping PostInstall execution.");
        }
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
            $ref = new \ReflectionClass($class);
            $moduleAttr = $ref->getAttributes(\Forge\Core\Module\Attributes\Module::class);

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
}
