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
    $this->setPermissions($remotePath);
    $this->runCommands($remotePath, $commands, $progressCallback);

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

      $result = $this->sshService->execute($fullCommand, $outputCallback, null, 1200);
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

  public function configureEnvironment(string $localPath, string $remotePath, array $envVars, ?callable $progressCallback = null, array $dbConfig = []): void
  {
    $localEnv = $localPath . '/.env';
    $localExample = $localPath . '/env-example';
    $remoteEnv = $remotePath . '/.env';

    if (file_exists($localEnv)) {
      if ($progressCallback !== null) {
        $progressCallback('Uploading local .env file...');
      }
      $this->sshService->upload($localEnv, $remoteEnv);
    } else {
      if ($progressCallback !== null) {
        $progressCallback('Local .env missing, using env-example and generating key...');
      }
      if (file_exists($localExample)) {
        $this->sshService->upload($localExample, $remoteEnv);
        $this->sshService->execute("cd {$remotePath} && php forge.php key:generate", $progressCallback);
      }
    }

    // Merge DB config into envVars
    if (!empty($dbConfig)) {
      $type = $dbConfig['database_type'] ?? 'mysql';
      if ($type === 'sqlite') {
        $envVars['DB_CONNECTION'] = 'sqlite';
        $envVars['DB_DATABASE'] = 'storage/database.sqlite';
      } else {
        $envVars['DB_CONNECTION'] = ($type === 'postgresql') ? 'pgsql' : 'mysql';
        if (isset($dbConfig['database_name']))
          $envVars['DB_DATABASE'] = $dbConfig['database_name'];
        if (isset($dbConfig['database_user']))
          $envVars['DB_USERNAME'] = $dbConfig['database_user'];
        if (isset($dbConfig['database_password']))
          $envVars['DB_PASSWORD'] = $dbConfig['database_password'];
        $envVars['DB_HOST'] = '127.0.0.1';
        $envVars['DB_PORT'] = ($type === 'postgresql') ? '5432' : '3306';
      }
    }

    if (!empty($envVars)) {
      if ($progressCallback !== null) {
        $progressCallback('Applying environment variables...');
      }

      // Read current .env content from server
      $result = $this->sshService->execute("cat {$remoteEnv}", null, null, 10);
      $envContent = $result['success'] ? $result['output'] : '';
      $lines = explode("\n", $envContent);
      $envMap = [];
      foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#'))
          continue;
        if (strpos($line, '=') !== false) {
          list($key, $val) = explode('=', $line, 2);
          $envMap[trim($key)] = trim($val);
        }
      }

      // Update with deployment and DB variables
      foreach ($envVars as $key => $value) {
        $envMap[$key] = $value;
      }

      // Rebuild content
      $newContent = "";
      foreach ($envMap as $key => $value) {
        $newContent .= "{$key}={$value}\n";
      }

      $this->sshService->uploadString($newContent, $remoteEnv, $progressCallback);
    }
  }

  private function setPermissions(string $remotePath): void
  {
    $this->sshService->execute("chown -R www-data:www-data {$remotePath}");
    $this->sshService->execute("find {$remotePath} -type d -exec chmod 755 {} \\;");
    $this->sshService->execute("find {$remotePath} -type f -exec chmod 644 {} \\;");
    $this->sshService->execute("chmod -R 775 {$remotePath}/storage");
  }

  private function runCommands(string $remotePath, array $commands, ?callable $outputCallback = null): void
  {
    foreach ($commands as $command) {
      $fullCommand = "cd {$remotePath} && {$command}";
      $result = $this->sshService->execute($fullCommand, $outputCallback);
      if (!$result['success']) {
        throw new \RuntimeException("Command failed: {$command}. Error: {$result['error']}");
      }
    }
  }
}
