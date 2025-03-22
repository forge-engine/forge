<?php

const MODULES_REGISTRY_FOLDER = __DIR__ . '/modules-registry';
const MODULES_SOURCE_FOLDER = __DIR__ . '/modules';
const MODULES_VERSIONS_FOLDER = MODULES_REGISTRY_FOLDER . '/modules';
const MODULES_MANIFEST_FILE = MODULES_REGISTRY_FOLDER . '/modules.json';

function createVersion(string $moduleVersionString): void
{
    echo "Creating module version: {$moduleVersionString}\n";

    list($moduleName, $version) = explode('@', $moduleVersionString);
    if (!$moduleName || !$version) {
        die("Error: Invalid module version format. Use module-name@version (e.g., forge-logger@1.2.0)\n");
    }

    $moduleSourceFolderName = generateModuleInstallFolderName($moduleName);
    $moduleSourcePath = MODULES_SOURCE_FOLDER . '/' . $moduleSourceFolderName;
    $registryModulePath = MODULES_VERSIONS_FOLDER . '/' . $moduleName;
    $registryVersionPath = $registryModulePath . '/' . $version;
    $manifestFilePath = MODULES_MANIFEST_FILE;
    $versionZipFilename = $version . '.zip';
    $versionZipFilePath = $registryVersionPath . '/' . $versionZipFilename;

    if (!is_dir($moduleSourcePath)) {
        die("Error: Module source folder not found: {$moduleSourcePath}. Ensure module '{$moduleName}' exists in the modules directory.\n");
    }

    if (!is_dir($registryVersionPath)) {
        mkdir($registryVersionPath, 0755, true);
    }

    echo "Zipping module folder...\n";
    if (!createZip($moduleSourcePath, $versionZipFilePath)) {
        die("Error creating ZIP archive for module {$moduleName} version {$version}.\n");
    }

    echo "Calculating SHA256 integrity hash...\n";
    $integrityHash = calculateFileIntegrity($versionZipFilePath);
    if (!$integrityHash) {
        die("Error calculating integrity hash for {$versionZipFilename}.\n");
    }

    echo "Updating module manifest (modules.json)...\n";
    $manifestData = readModulesManifest($manifestFilePath);
    if ($manifestData === null) {
        die("Error reading module manifest.\n");
    }

    if (!isset($manifestData[$moduleName])) {
        $manifestData[$moduleName] = [
            'latest' => $version,
            'versions' => [],
        ];
    }
    $manifestData[$moduleName]['versions'][$version] = [
        'description' => "Version " . $version . " of " . $moduleName,
        'url' => $moduleName . '/' . $version,
        'integrity' => $integrityHash,
    ];
    $manifestData[$moduleName]['latest'] = $version;


    if (!writeModulesManifest($manifestFilePath, $manifestData)) {
        die("Error writing updated module manifest.\n");
    }

    echo "Module {$moduleName} version {$version} created and manifest updated successfully!\n";
    echo "ZIP file saved to: {$versionZipFilePath}\n";
    echo "Manifest updated in: {$manifestFilePath}\n";

    echo "Committing changes to module registry...\n";
    $registryDir = MODULES_REGISTRY_FOLDER;
    chdir($registryDir);

    $commitMessage = "Add module " . $moduleName . " version v" . $version;
    $gitAddResult = runGitCommand('add', ['.']);
    if ($gitAddResult['exitCode'] !== 0) {
        chdir(__DIR__);
        die("Git add failed: " . $gitAddResult['output']);
    }

    $gitCommitResult = runGitCommand('commit', ['-m', $commitMessage]);
    if ($gitCommitResult['exitCode'] !== 0) {
        chdir(__DIR__);
        die("Git commit failed: " . $gitCommitResult['output']);
    }

    chdir(__DIR__);
    echo "Changes committed to module registry.\n";
}

function uploadRegistry(): void
{
    echo "Uploading module registry...\n";
    $registryDir = MODULES_REGISTRY_FOLDER;
    chdir($registryDir);

    $gitPushResult = runGitCommand('push', ['origin', 'main']);
    if ($gitPushResult['exitCode'] !== 0) {
        chdir(__DIR__);
        die("Git push failed: " . $gitPushResult['output']);
    }

    chdir(__DIR__);
    echo "Module registry uploaded successfully!\n";
}

function listVersions(): void
{
    $manifestFilePath = MODULES_MANIFEST_FILE;

    echo "Available Forge Modules and Versions:\n";

    $manifestData = readModulesManifest($manifestFilePath);
    if (!$manifestData) {
        echo "Error: Could not read module manifest (forge.json).\n";
        return;
    }

    if (empty($manifestData)) {
        echo "No modules found in the manifest.\n";
        return;
    }

    echo "-----------------------------------\n";
    foreach ($manifestData as $moduleName => $moduleInfo) {
        echo "Module: " . $moduleName . "\n";
        echo "  Latest Version: " . ($moduleInfo['latest'] ?? 'Not defined') . "\n";
        echo "  Available Versions:\n";
        $versions = $moduleInfo['versions'] ?? [];
        if (empty($versions)) {
            echo "    No versions defined.\n";
        } else {
            foreach ($versions as $versionName => $versionDetails) {
                echo "    - " . $versionName . "\n";
            }
        }
        echo "-----------------------------------\n";
    }
}

function displayHelp(): void
{
    echo "Forge Module Registry Tool (modules.php)\n\n";
    echo "Usage: php modules.php <command> [options]\n\n";
    echo "Available commands:\n";
    echo "  create-version <module-name>@<version>  Creates a new module version (zips module, updates manifest, commits changes).\n";
    echo "  list-versions           Lists available modules and their versions from the manifest.\n";
    echo "  publish                 Pushes the module registry changes to the remote repository.\n";
    echo "  help                    Displays this help message.\n";
}

$command = $argv[1] ?? 'help';
$versionArg = $argv[2] ?? null;

switch ($command) {
    case 'create-version':
        if (!$versionArg) {
            echo "Error: Module name and version are required for create-version command.\n\n";
            displayHelp();
            exit(1);
        }
        createVersion($versionArg);
        break;
    case 'list-versions':
        listVersions();
        break;
    case 'publish':
        uploadRegistry();
        break;
    case 'help':
    default:
        displayHelp();
        break;
}

/**
 * Creates a ZIP archive of a directory.
 *
 * @param string $sourceDir Path to the directory to be zipped.
 * @param string $zipFilePath Path where the ZIP file should be created.
 * @return bool True on success, false on failure.
 */
function createZip(string $sourceDir, string $zipFilePath): bool
{
    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $sourceDir = rtrim($sourceDir, '/');

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourceDir) + 1);

            $zip->addFile($filePath, $relativePath);
        }
    }

    return $zip->close();
}

/**
 * Calculates the SHA256 integrity of a file.
 *
 * @param string $filePath Path to the file to verify.
 * @return string|bool SHA256 hash string on success, false on failure.
 */
function calculateFileIntegrity(string $filePath): string|bool
{
    if (!file_exists($filePath)) {
        return false;
    }
    return hash_file('sha256', $filePath);
}


/**
 * Reads and decodes the modules manifest (forge.json) file.
 *
 * @param string $manifestFilePath Path to the forge.json manifest file.
 * @return array|null Associative array of manifest data on success, null on failure.
 */
function readModulesManifest(string $manifestFilePath): ?array
{
    if (!file_exists($manifestFilePath)) {
        return null;
    }
    $content = file_get_contents($manifestFilePath);
    if ($content === false) {
        return null;
    }
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    return is_array($data) ? $data : null;
}


/**
 * Encodes and writes the modules manifest data to the forge.json file.
 *
 * @param string $manifestFilePath Path to the forge.json manifest file.
 * @param array $manifestData Associative array of manifest data to write.
 * @return bool True on success, false on failure.
 */
function writeModulesManifest(string $manifestFilePath, array $manifestData): bool
{
    $jsonContent = json_encode($manifestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonContent === false) {
        return false; // JSON encoding error
    }
    if (file_put_contents($manifestFilePath, $jsonContent) !== false) {
        return true;
    }
    return false; // File writing error
}

/**
 * Runs a Git command in a specified directory.
 *
 * @param string $command Git command to run (e.g., 'add', 'commit', 'push').
 * @param array $arguments Array of arguments for the Git command.
 * @return array Associative array containing 'exitCode' and 'output'.
 */
function runGitCommand(string $command, array $arguments): array
{
    $commandString = "git " . $command . " " . implode(" ", array_map('escapeshellarg', $arguments));
    $output = [];
    $exitCode = 0;
    exec($commandString . " 2>&1", $output, $exitCode);
    return [
        'exitCode' => $exitCode,
        'output' => implode("\n", $output),
    ];
}

/**
 * Generates PascalCase folder name from module full name.
 *
 * @param string $fullName Module full name (e.g., forge-logger).
 * @return string PascalCase folder name (e.g., ForgeLogger).
 */
function generateModuleInstallFolderName(string $fullName): string
{
    $parts = explode('-', $fullName);
    $pascalCaseName = '';
    foreach ($parts as $part) {
        $pascalCaseName .= ucfirst($part);
    }
    return $pascalCaseName;
}
