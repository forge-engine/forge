<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class DeploymentService
{
  public function __construct(
    private readonly SshService $sshService,
    private readonly ForgeIgnoreService $ignoreService,
    private readonly ProjectZipService $zipService
  ) {
  }

  public function deploy(string $localPath, string $remotePath, array $commands = [], array $envVars = [], ?callable $progressCallback = null): bool
  {
    $progress = function (string $message) use ($progressCallback) {
      if ($progressCallback !== null) {
        $progressCallback($message);
      }
    };

    $this->createRemoteDirectory($remotePath);
    $this->uploadProject($localPath, $remotePath, $progress);
    $this->configureEnvironment($remotePath, $envVars, $progress);
    $this->setPermissions($remotePath);
    $this->runCommands($remotePath, $commands);

    return true;
  }

  public function connect(string $host, int $port, string $username, ?string $privateKeyPath = null, ?string $publicKeyPath = null, ?string $passphrase = null): bool
  {
    return $this->sshService->connect($host, $port, $username, $privateKeyPath, $publicKeyPath, $passphrase);
  }

  public function runPostDeploymentCommands(string $remotePath, array $commands, ?callable $outputCallback = null): void
  {
    if (empty($commands)) {
      return;
    }

    foreach ($commands as $command) {
      if (str_starts_with($command, 'php forge.php')) {
        $fullCommand = "cd {$remotePath} && {$command}";
      } else {
        $fullCommand = "cd {$remotePath} && php forge.php {$command}";
      }

      $result = $this->sshService->execute($fullCommand, $outputCallback);
      if (!$result['success']) {
        $error = $result['error'] ?: $result['output'];
        throw new \RuntimeException("Post-deployment command failed: {$command}. Error: {$error}");
      }
    }
  }

  private function createRemoteDirectory(string $remotePath): void
  {
    $this->sshService->execute("mkdir -p {$remotePath}");
  }

  private function uploadProject(string $localPath, string $remotePath, ?callable $progressCallback = null): void
  {
    $progress = function (string $message) use ($progressCallback) {
      if ($progressCallback !== null) {
        $progressCallback($message);
      }
    };

    $progress('Creating project archive...');
    $zipPath = $this->zipService->createZip($localPath, $progress);

    try {
      $fileSize = filesize($zipPath);
      $fileSizeMb = round($fileSize / 1024 / 1024, 2);
      $progress("Uploading archive ({$fileSizeMb}MB)...");

      $remoteZipPath = '/tmp/forge-deployment-' . uniqid() . '.zip';
      $this->sshService->reconnect();

      $uploadProgress = function (int $bytesUploaded, int $totalBytes) use ($progress, $fileSizeMb) {
        $percent = ($totalBytes > 0) ? round(($bytesUploaded / $totalBytes) * 100) : 0;
        $uploadedMb = round($bytesUploaded / 1024 / 1024, 2);
        $progress("Uploading: {$uploadedMb}MB / {$fileSizeMb}MB ({$percent}%)");
      };

      $this->sshService->upload($zipPath, $remoteZipPath, $uploadProgress);

      $progress('Verifying uploaded archive...');
      $verifyZip = $this->sshService->execute("test -f " . escapeshellarg($remoteZipPath) . " && echo 'ok'", null, null, 10);
      if (trim($verifyZip['output'] ?? '') !== 'ok') {
        throw new \RuntimeException('Uploaded zip file not found on server: ' . $remoteZipPath);
      }

      $progress('Checking for unzip...');
      $unzipCheck = $this->sshService->execute('which unzip', null, null, 10);
      if (!$unzipCheck['success'] || trim($unzipCheck['output']) === '') {
        $progress('Installing unzip...');
        $installResult = $this->sshService->execute('export DEBIAN_FRONTEND=noninteractive && apt-get install -y unzip', null, null, 120);
        if (!$installResult['success']) {
          $this->sshService->execute("rm -f " . escapeshellarg($remoteZipPath));
          throw new \RuntimeException('Failed to install unzip: ' . $installResult['error']);
        }
      }

      $progress('Extracting archive on server...');

      $this->sshService->execute("mkdir -p " . escapeshellarg($remotePath), null, null, 10);

      $extractCommand = sprintf(
        'cd %s && unzip -o %s 2>&1',
        escapeshellarg($remotePath),
        escapeshellarg($remoteZipPath)
      );

      $result = $this->sshService->execute($extractCommand, function ($line) use ($progress) {
        $trimmed = trim($line);
        if ($trimmed !== '' && !str_starts_with($trimmed, 'Archive:') && !str_starts_with($trimmed, 'inflating:')) {
          $progress("  " . $trimmed);
        }
      }, null, 300);

      if (!$result['success']) {
        $this->sshService->execute("rm -f " . escapeshellarg($remoteZipPath));
        throw new \RuntimeException('Failed to extract archive: ' . $result['error']);
      }

      $progress('Cleaning up temporary archive...');
      $this->sshService->execute("rm -f " . escapeshellarg($remoteZipPath), null, null, 10);

    } finally {
      $this->zipService->cleanup($zipPath);
    }
  }

  private function configureEnvironment(string $remotePath, array $envVars, ?callable $progressCallback = null): void
  {
    if (empty($envVars)) {
      return;
    }

    $envContent = '';
    foreach ($envVars as $key => $value) {
      $envContent .= "{$key}={$value}\n";
    }

    $this->sshService->uploadString($envContent, $remotePath . '/.env', $progressCallback);
  }

  private function setPermissions(string $remotePath): void
  {
    $this->sshService->execute("chown -R www-data:www-data {$remotePath}");
    $this->sshService->execute("find {$remotePath} -type d -exec chmod 755 {} \\;");
    $this->sshService->execute("find {$remotePath} -type f -exec chmod 644 {} \\;");
    $this->sshService->execute("chmod -R 775 {$remotePath}/storage");
  }

  private function runCommands(string $remotePath, array $commands): void
  {
    foreach ($commands as $command) {
      $fullCommand = "cd {$remotePath} && {$command}";
      $result = $this->sshService->execute($fullCommand);
      if (!$result['success']) {
        throw new \RuntimeException("Command failed: {$command}. Error: {$result['error']}");
      }
    }
  }
}
