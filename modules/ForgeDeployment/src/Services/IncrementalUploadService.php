<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class IncrementalUploadService
{
  public function __construct(
    private readonly SshService $sshService,
    private readonly ForgeIgnoreService $ignoreService
  ) {
    $this->ignoreService->load(BASE_PATH);
  }

  public function uploadChangedFiles(array $changedFiles, string $localPath, string $remotePath, ?callable $progressCallback = null): void
  {
    if (empty($changedFiles)) {
      if ($progressCallback !== null) {
        $progressCallback('No files to upload.');
      }
      return;
    }

    $totalFiles = count($changedFiles);
    $uploadedCount = 0;
    $skippedCount = 0;

    if ($progressCallback !== null) {
      $progressCallback("Uploading {$totalFiles} changed file(s)...");
    }

    foreach ($changedFiles as $relativeFilePath) {
      $localFilePath = $localPath . '/' . $relativeFilePath;
      $remoteFilePath = $remotePath . '/' . $relativeFilePath;

      // Safety check: ensure file is not ignored (defense-in-depth)
      if ($this->ignoreService->shouldIgnore($localFilePath)) {
        $skippedCount++;
        if ($progressCallback !== null) {
          $progressCallback("Skipping ignored file: {$relativeFilePath}");
        }
        continue;
      }

      // Check if local file exists (it might be a deletion)
      if (!file_exists($localFilePath)) {
        // File was deleted - we could handle this in the future
        // For now, skip it
        $skippedCount++;
        if ($progressCallback !== null) {
          $progressCallback("Skipping deleted file: {$relativeFilePath}");
        }
        continue;
      }

      // Skip directories (git diff might include them)
      if (is_dir($localFilePath)) {
        continue;
      }

      // Upload the file
      $uploadProgress = function (int $bytesUploaded, int $totalBytes) use ($progressCallback, $relativeFilePath, $uploadedCount, $totalFiles) {
        if ($progressCallback !== null && $totalBytes > 0) {
          $percent = round(($bytesUploaded / $totalBytes) * 100);
          $progressCallback("  [{$uploadedCount}/{$totalFiles}] {$relativeFilePath} ({$percent}%)");
        }
      };

      $success = $this->sshService->upload($localFilePath, $remoteFilePath, $uploadProgress);

      if ($success) {
        $uploadedCount++;
        if ($progressCallback !== null) {
          $progressCallback("  ✓ {$relativeFilePath}");
        }
      } else {
        throw new \RuntimeException("Failed to upload file: {$relativeFilePath}");
      }
    }

    if ($progressCallback !== null) {
      $progressCallback("Upload complete: {$uploadedCount} file(s) uploaded, {$skippedCount} skipped");
    }
  }
}
