<?php

const FRAMEWORK_REGISTRY_FOLDER = __DIR__ . '/framework-registry';
const FRAMEWORK_ENGINE_FOLDER = __DIR__ . '/engine';
const FRAMEWORK_VERSIONS_FOLDER = FRAMEWORK_REGISTRY_FOLDER . '/versions';
const FRAMEWORK_MANIFEST_FILE = FRAMEWORK_REGISTRY_FOLDER . '/forge.json';

function createVersion(string $version): void
{
    echo "Creating framework version: {$version}\n";

    $engineFolderPath = FRAMEWORK_ENGINE_FOLDER;
    $registryFolderPath = FRAMEWORK_REGISTRY_FOLDER;
    $versionsFolderPath = FRAMEWORK_VERSIONS_FOLDER;
    $manifestFilePath = FRAMEWORK_MANIFEST_FILE;
    $versionZipFilename = $version . '.zip';
    $versionZipFilePath = $versionsFolderPath . '/' . $versionZipFilename;

    if (!is_dir($versionsFolderPath)) {
        mkdir($versionsFolderPath, 0755, true);
    }

    echo "Zipping engine folder...\n";
    if (!createZip($engineFolderPath, $versionZipFilePath)) {
        die("Error creating ZIP archive for version {$version}.\n");
    }

    echo "Calculating SHA256 integrity hash...\n";
    $integrityHash = calculateFileIntegrity($versionZipFilePath);
    if (!$integrityHash) {
        die("Error calculating integrity hash for {$versionZipFilename}.\n");
    }

    echo "Updating framework manifest (forge.json)...\n";
    $manifestData = readFrameworkManifest($manifestFilePath);
    if ($manifestData == null) {
        die("Error reading framework manifest.\n");
    }

    $manifestData['versions'][$version] = [
        'download_url' => 'versions/' . $versionZipFilename,
        'integrity' => $integrityHash,
        'release_date' => date('Y-m-d'),
        'release_notes_url' => 'https://github.com/forge-engine/forge/blob/main/CHANGELOG.md',
        'require' => $manifestData['require'] ?? [],
    ];
    $manifestData['versions']['latest'] = $version;

    if (!writeFrameworkManifest($manifestFilePath, $manifestData)) {
        die("Error writing updated framework manifest.\n");
    }

    echo "Framework version {$version} created and manifest updated successfully!\n";
    echo "ZIP file saved to: {$versionZipFilePath}\n";
    echo "Manifest updated in: {$manifestFilePath}\n";

    // --- 5. Git Commit Changes in framework-registry ---
    echo "Committing changes to framework registry...\n";
    $registryDir = FRAMEWORK_REGISTRY_FOLDER;
    chdir($registryDir);

    $commitMessage = "Add framework version v" . $version;
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
    echo "Changes committed to framework registry.\n";
}

function uploadRegistry(): void
{
    echo "Uploading framework registry...\n";
    $registryDir = FRAMEWORK_REGISTRY_FOLDER;
    chdir($registryDir);

    $gitPushResult = runGitCommand('push', ['origin', 'main']);
    if ($gitPushResult['exitCode'] !== 0) {
        chdir(__DIR__);
        die("Git push failed: " . $gitPushResult['output']);
    }

    chdir(__DIR__);
    echo "Framework registry uploaded successfully!\n";
}

function listVersions(): void
{
    $registryFolderPath = __DIR__ . '/framework-registry';
    $manifestFilePath = $registryFolderPath . '/forge.json';

    echo "Available Forge Framework Versions:\n";

    $manifestData = readFrameworkManifest($manifestFilePath);
    if (!$manifestData) {
        echo "Error: Could not read framework manifest (forge.json).\n";
        return;
    }

    $versions = $manifestData['versions'] ?? [];
    if (empty($versions) || count($versions) <= 1 && isset($versions['latest'])) {
        echo "No framework versions found in the manifest.\n";
        return;
    }

    echo "-----------------------------------\n";
    foreach ($versions as $versionName => $versionDetails) {
        if ($versionName !== 'latest') {
            echo "- " . $versionName . "\n";
        }
    }
    echo "-----------------------------------\n";
    echo "Latest Version: " . ($versions['latest'] ?? 'Not defined') . "\n";
}

function displayHelp(): void
{
    echo "Forge Framework Registry Tool (framework.php)\n\n";
    echo "Usage: php framework.php <command> [options]\n\n";
    echo "Available commands:\n";
    echo "  create-version <version>  Creates a new framework version (zips engine, updates manifest, commits changes).\n";
    echo "  list-versions           Lists available framework versions from the manifest.\n";
    echo "  publish                 Pushes the framework registry changes to the remote repository.\n";
    echo "  help                    Displays this help message.\n";
}

$command = $argv[1] ?? 'help';
$versionArg = $argv[2] ?? null;

switch ($command) {
    case 'create-version':
        if (!$versionArg) {
            echo "Error: Version number is required for create-version command.\n\n";
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
 * Reads and decodes the framework manifest (forge.json) file.
 *
 * @param string $manifestFilePath Path to the forge.json manifest file.
 * @return array|null Associative array of manifest data on success, null on failure.
 */
function readFrameworkManifest(string $manifestFilePath): ?array
{
    if (!file_exists($manifestFilePath)) {
        return null;
    }
    $content = @file_get_contents($manifestFilePath);
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
 * Encodes and writes the framework manifest data to the forge.json file.
 *
 * @param string $manifestFilePath Path to the forge.json manifest file.
 * @param array $manifestData Associative array of manifest data to write.
 * @return bool True on success, false on failure.
 */
function writeFrameworkManifest(string $manifestFilePath, array $manifestData): bool
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
