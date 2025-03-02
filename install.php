<?php
const FRAMEWORK_REPO_URL = 'https://github.com/forge-engine/framework';
const FRAMEWORK_FORGE_JSON_PATH_IN_REPO = 'forge.json';
const FRAMEWORK_REPO_BRANCH = 'main';

// 2. Construct Raw GitHub URL for forge.json
$frameworkForgeJsonUrl = generateRawGithubUrl(FRAMEWORK_REPO_URL, FRAMEWORK_REPO_BRANCH, FRAMEWORK_FORGE_JSON_PATH_IN_REPO);
echo "Fetching framework manifest from: " . $frameworkForgeJsonUrl . "\n";

// 3. Fetch Framework forge.json Content
$frameworkManifestJson = @file_get_contents($frameworkForgeJsonUrl);
if (!$frameworkManifestJson) {
    die("Error fetching framework manifest from GitHub. URL: " . $frameworkForgeJsonUrl . "\n");
}
$frameworkManifest = json_decode($frameworkManifestJson, true);
if (!$frameworkManifest || !is_array($frameworkManifest)) {
    die("Error decoding framework manifest JSON from GitHub.\n");
}

// 4. Determine Framework Version to Install (using 'latest' from manifest)
$versionToInstall = $frameworkManifest['versions']['latest'] ?? null;
if (!$versionToInstall) {
    die("Error: 'latest' version not defined in framework manifest.\n");
}

$versionDetails = $frameworkManifest['versions'][$versionToInstall];
if (!$versionDetails) {
    die("Version '{$versionToInstall}' details not found in framework manifest.\n");
}

$downloadUrl = $versionDetails['download_url'];
$integrityHash = $versionDetails['integrity'];

// 5. Download Framework ZIP
echo "Downloading Forge Framework version {$versionToInstall} from: " . $downloadUrl . "\n";
$zipFilePath = downloadFile($downloadUrl, 'engine.zip');

// 6. Verify Integrity
echo "Verifying integrity...\n";
if (!verifyFileIntegrity($zipFilePath, $integrityHash)) { // Function to verify hash
    die("Integrity check failed! Downloaded file is corrupted or tampered.\n");
}

// 7. Extract Framework Files
echo "Extracting framework files...\n";
$extractionPath = './'; // Extract to project root for simplicity initially
if (!extractZip($zipFilePath, $extractionPath)) { // Function to extract zip
    die("Error extracting framework files.\n");
}

// 8. Cleanup (optional - remove downloaded zip)
unlink($zipFilePath);

// 9. Post-Installation Message
echo "\nForge Framework version {$versionToInstall} installed successfully!\n";
echo "You can now use 'php forge.php' to manage your project and modules.\n";
echo "Run 'php forge.php list' to see available commands.\n";

// --- Helper Functions (Implement these - e.g., generateRawGithubUrl, downloadFile, verifyFileIntegrity, extractZip) ---

/**
 * Generates the raw GitHub URL for a file in a repository.
 *
 * @param string $repoUrl GitHub repository URL (e.g., https://github.com/user/repo)
 * @param string $branch Branch name (e.g., main)
 * @param string $filePathInRepo Path to the file within the repository (e.g., forge.json)
 * @return string Raw GitHub URL
 */
function generateRawGithubUrl(string $repoUrl, string $branch, string $filePathInRepo): string
{
    $repoBaseRawUrl = rtrim(str_replace('github.com', 'raw.githubusercontent.com', $repoUrl), '/');
    return $repoBaseRawUrl . '/' . $branch . '/' . $filePathInRepo;
}


/**
 * Downloads a file from a URL to a destination path.
 *
 * @param string $url URL of the file to download.
 * @param string $destinationPath Path to save the downloaded file.
 * @return string|bool Path to the downloaded file on success, false on failure.
 */
function downloadFile(string $url, string $destinationPath): string|bool
{
    $fileContent = @file_get_contents($url);
    if ($fileContent === false) {
        return false;
    }
    if (file_put_contents($destinationPath, $fileContent) !== false) {
        return $destinationPath;
    }
    return false;
}

/**
 * Verifies the SHA256 integrity of a file.
 *
 * @param string $filePath Path to the file to verify.
 * @param string $expectedHash Expected SHA256 hash.
 * @return bool True if integrity is verified, false otherwise.
 */
function verifyFileIntegrity(string $filePath, string $expectedHash): bool
{
    if (!file_exists($filePath)) {
        return false;
    }
    $calculatedHash = hash_file('sha256', $filePath);
    return $calculatedHash === $expectedHash;
}

/**
 * Extracts a ZIP archive to a destination directory.
 *
 * @param string $zipPath Path to the ZIP archive.
 * @param string $destinationPath Path to extract the contents to.
 * @return bool True on successful extraction, false otherwise.
 */
function extractZip(string $zipPath, string $destinationPath): bool
{
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === TRUE) {
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $zip->extractTo($destinationPath);
        $zip->close();
        return true;
    } else {
        return false;
    }
}