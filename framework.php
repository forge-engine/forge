<?php

// framework.php - Forge Framework Registry Management CLI

function createVersion(string $version): void
{
    echo "Creating framework version: {$version}\n";

    $engineFolderPath = __DIR__ . '/engine';
    $registryFolderPath = __DIR__ . '/framework-registry';
    $versionsFolderPath = $registryFolderPath . '/versions';
    $manifestFilePath = $registryFolderPath . '/forge.json';
    $versionZipFilename = $version . '.zip';
    $versionZipFilePath = $versionsFolderPath . '/' . $versionZipFilename;

    // 1. Ensure versions folder exists
    if (!is_dir($versionsFolderPath)) {
        mkdir($versionsFolderPath, 0755, true);
    }

    // 2. Create ZIP archive of the engine folder
    echo "Zipping engine folder...\n";
    if (!createZip($engineFolderPath, $versionZipFilePath)) {
        die("Error creating ZIP archive for version {$version}.\n");
    }

    // 3. Calculate SHA256 integrity hash of the ZIP file
    echo "Calculating SHA256 integrity hash...\n";
    $integrityHash = calculateFileIntegrity($versionZipFilePath);
    if (!$integrityHash) {
        die("Error calculating integrity hash for {$versionZipFilename}.\n");
    }

    // 4. Read and update the framework registry manifest (forge.json)
    echo "Updating framework manifest (forge.json)...\n";
    $manifestData = readFrameworkManifest($manifestFilePath);
    if (!$manifestData) {
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
}

function displayHelp(): void
{
    echo "Forge Framework Registry Tool (framework.php)\n\n";
    echo "Usage: php framework.php <command> [options]\n\n";
    echo "Available commands:\n";
    echo "  create-version <version>  Creates a new framework version (zips engine, updates manifest).\n";
    echo "  list-versions           Lists available framework versions from the manifest.\n";
    echo "  help                    Displays this help message.\n";
    // ... more commands can be added later (e.g., upload-registry, list-versions)
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
    if (empty($versions) || count($versions) <= 1 && isset($versions['latest'])) { // Check if versions are empty or only contains 'latest'
        echo "No framework versions found in the manifest.\n";
        return;
    }

    echo "-----------------------------------\n";
    foreach ($versions as $versionName => $versionDetails) {
        if ($versionName !== 'latest') { // Exclude 'latest' pseudo-version from listing
            echo "- " . $versionName . "\n";
        }
    }
    echo "-----------------------------------\n";
    echo "Latest Version: " . ($versions['latest'] ?? 'Not defined') . "\n";
}

// --- Command Handling ---
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
    case 'list-version':
        listVersions();
        break;
    case 'help':
    default:
        displayHelp();
        break;
}

// --- Helper Functions (Implement these - e.g., createZip, calculateFileIntegrity, readFrameworkManifest, writeFrameworkManifest) ---

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
    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        return false;
    }

    $sourceDir = rtrim($sourceDir, '/'); // Ensure source directory path doesn't end with a slash

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