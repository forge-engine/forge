<?php

namespace Forge\Modules\ForgePackageManager\Src\Services;

use Forge\Core\Helpers\App;
use Forge\Core\Helpers\Strings;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgePackageManager\Src\Contracts\PackageManagerInterface;
use ZipArchive;

class PackageManager implements PackageManagerInterface
{
    use OutputHelper;

    private const OFFICIAL_REGISTRY_NAME = 'forge-engine-modules';
    private const OFFICIAL_REGISTRY_BASE_URL = 'https://github.com/forge-engine/modules';
    private const OFFICIAL_REGISTRY_BRANCH = 'main';

    private string $modulesPath;
    private array $registries;
    private string $cachePath;
    private int $cacheTtl;
    private string $integrityHash;

    public function __construct()
    {
        $config = App::config();
        $this->registries = $config->get('package_manager.registry', []);
        $this->cacheTtl = $config->get('package_manager.cache_ttl', 3600);
        $this->modulesPath = BASE_PATH . '/modules/';
        $this->cachePath = BASE_PATH . '/storage/framework/modules/cache/';

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
        if (!is_dir($this->modulesPath)) {
            mkdir($this->modulesPath, 0755, true);
        }
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
                $integrityHash = $this->downloadFile($downloadUrl, $moduleCachePath);
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

    private function downloadModuleFromGit(string $repoUrl, string $branch, string $modulePathInRepo, string $destinationPath): bool|string
    {
        $tempDir = sys_get_temp_dir() . '/forge-module-download-' . uniqid(); // Create a unique temp dir
        if (!mkdir($tempDir, 0777, true)) {
            $this->error("Failed to create temporary directory for Git clone.");
            return false;
        }

        $cloneCommand = "git clone --depth 1 -b " . escapeshellarg($branch) . " " . escapeshellarg($repoUrl) . " " . escapeshellarg($tempDir);
        exec($cloneCommand, $cloneOutput, $cloneReturnVar);

        if ($cloneReturnVar !== 0) {
            $this->error("Git clone failed. Command: {$cloneCommand}");
            $this->error("Git clone output: " . implode("\n", $cloneOutput));
            $this->removeDirectory($tempDir); // Cleanup temp dir even on failure
            return false;
        }

        $moduleZipPathInRepo = $tempDir . '/' . 'modules/' . $modulePathInRepo . '/' . basename($modulePathInRepo) . '.zip'; // Assuming ZIP is in modules/<module>/<version>/<version>.zip

        if (!file_exists($moduleZipPathInRepo)) {
            $this->error("Module ZIP file not found in cloned repository at: {$moduleZipPathInRepo}. Ensure the path and filename are correct in your registry.");
            $this->removeDirectory($tempDir);
            return false;
        }

        if (!rename($moduleZipPathInRepo, $destinationPath)) { // Move the ZIP to the cache destination
            $this->error("Failed to move downloaded module ZIP to cache destination: {$destinationPath}");
            $this->removeDirectory($tempDir);
            return false;
        }

        $integrityHash = hash_file('sha256', $destinationPath); // Calculate hash after moving to destination
        $this->removeDirectory($tempDir); // Cleanup temp dir after successful download
        return $integrityHash;
    }

    public function installModule(string $moduleName, ?string $version = null): void
    {
        // 1. Determine registry and module info
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
        $registryRawBaseUrl = $this->getRegistryRawBaseUrl($registryDetails);
        $moduleInstallFolderName = $this->generateModuleInstallFolderName($moduleName);
        $moduleCacheFileName = $moduleInstallFolderName . '-' . $versionToInstall . '.zip';
        $moduleCachePath = $this->getCachePath() . $moduleCacheFileName;
        $moduleInstallPath = $this->getModulesPath() . $moduleInstallFolderName;

        $githubZipUrl = $this->generateGithubZipUrl($registryRawBaseUrl, $registryDetails['branch'], $moduleDownloadPathInRepo);

        if (!file_exists($moduleCachePath)) {
            $this->info("Downloading module {$moduleName} version {$versionToInstall} from {$githubZipUrl}...");
            $integrityHash = $this->downloadFile($githubZipUrl, $moduleCachePath);
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

        $this->success("Module {$moduleName} version {$versionToInstall} installed successfully.");
    }

    public function removeModule(string $moduleName): void
    {
        $this->error("Remove module not yet implemented.");
    }

    private function getModuleInfo(string $moduleName, ?string $version = null): ?array
    {
        $registryDetails = $this->getRegistryDetailsForModule($moduleName);
        $modulesJsonUrl = $this->getModulesJsonUrl($registryDetails);

        $cacheKey = md5($modulesJsonUrl);
        $cacheFile = $this->getCachePath() . $cacheKey . '.cache';
        $modulesData = null;

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $this->cacheTtl)) {
            $this->info("Using cached module list from " . (isset($registryDetails['name']) ? $registryDetails['name'] : $modulesJsonUrl) . ".");
            $modulesData = json_decode(file_get_contents($cacheFile), true);
        }

        if (!is_array($modulesData) || !isset($modulesData[$moduleName])) {
            $this->info("Fetching module list from " . (isset($registryDetails['name']) ? $registryDetails['name'] : $modulesJsonUrl) . "...");
            $modulesJsonContent = @file_get_contents($modulesJsonUrl);

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

    private function getRegistryDetailsForModule(string $moduleName): array
    {
        if (strpos($moduleName, 'forge-') === 0) {
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
        $registryBaseUrl = rtrim($registryDetails['url'], '/');
        $branch = isset($registryDetails['branch']) ? $registryDetails['branch'] : 'main';
        return "https://raw.githubusercontent.com/" . preg_replace('#^https?://(?:www\.)?github\.com/([^/]+)/([^/]+).*$#i', '$1/$2', $registryBaseUrl) . "/" . $branch;
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

    private function downloadFile(string $url, string $destination): bool|string
    {
        $fileContent = @file_get_contents($url);
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
        if ($zip->open($zipPath) === TRUE) {

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

    private function updateForgeJson(string $moduleName, string $version): void
    {
        $forgeJsonPath = BASE_PATH . '/forge.json';
        $forgeConfig = $this->readForgeJson();
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
            return ['modules' => []];
        }
        $content = file_get_contents($forgeJsonPath);
        return json_decode($content, true) ?? ['modules' => []];
    }

    private function writeForgeJson(array $data): void
    {
        $forgeJsonPath = BASE_PATH . '/forge.json';
        file_put_contents($forgeJsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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
}