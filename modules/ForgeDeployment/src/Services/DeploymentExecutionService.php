<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class DeploymentExecutionService
{
    private const LOG_DIR = 'storage/framework/deployments';
    private const MAX_LOG_SIZE = 10 * 1024 * 1024;

    public function executeDeployment(string $command, array $args = [], ?callable $outputCallback = null): array
    {
        $deploymentId = $this->generateDeploymentId($command);
        $logPath = $this->getLogPath($deploymentId);

        $phpExecutable = $this->getPhpExecutable();
        $forgePath = BASE_PATH . '/forge.php';

        $argsString = $this->buildArgsString($args);
        $fullCommand = sprintf(
            'cd %s && %s %s %s %s 2>&1',
            escapeshellarg(BASE_PATH),
            escapeshellarg($phpExecutable),
            escapeshellarg($forgePath),
            escapeshellarg($command),
            $argsString
        );

        $output = '';
        $errorOutput = '';
        $exitCode = 1;

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($fullCommand, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            $error = 'Failed to start deployment process';
            $this->storeDeploymentLog($deploymentId, $error);
            return [
                'success' => false,
                'deployment_id' => $deploymentId,
                'error' => $error,
                'output' => '',
            ];
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $startTime = time();
        $timeout = 3600;

        while (true) {
            $status = proc_get_status($process);

            if (!$status['running']) {
                $exitCode = $status['exitcode'];
                break;
            }

            if (time() - $startTime > $timeout) {
                proc_terminate($process);
                $errorOutput .= "\n[ERROR] Deployment timed out after {$timeout} seconds\n";
                break;
            }

            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;

            if (stream_select($read, $write, $except, 1) > 0) {
                if (in_array($pipes[1], $read)) {
                    $chunk = stream_get_contents($pipes[1]);
                    if ($chunk !== false && $chunk !== '') {
                        $output .= $chunk;
                        if ($outputCallback !== null) {
                            $outputCallback($chunk);
                        }
                    }
                }
                if (in_array($pipes[2], $read)) {
                    $chunk = stream_get_contents($pipes[2]);
                    if ($chunk !== false && $chunk !== '') {
                        $errorOutput .= $chunk;
                        if ($outputCallback !== null) {
                            $outputCallback($chunk);
                        }
                    }
                }
            }

            usleep(100000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $fullOutput = $output . $errorOutput;
        $this->storeDeploymentLog($deploymentId, $fullOutput);

        return [
            'success' => $exitCode === 0,
            'deployment_id' => $deploymentId,
            'exit_code' => $exitCode,
            'output' => $this->sanitizeOutput($fullOutput),
        ];
    }

    public function getPhpExecutable(): string
    {
        static $phpPath = null;

        if ($phpPath !== null) {
            return $phpPath;
        }

        $possiblePaths = [];

        $whichOutput = [];
        $whichReturnCode = 0;
        @exec('which php 2>/dev/null', $whichOutput, $whichReturnCode);
        if ($whichReturnCode === 0 && !empty($whichOutput[0])) {
            $whichPath = trim($whichOutput[0]);
            if ($whichPath && !str_contains($whichPath, 'fpm') && file_exists($whichPath)) {
                $possiblePaths[] = $whichPath;
            }
        }

        if (defined('PHP_BINARY') && PHP_BINARY) {
            $phpBinary = PHP_BINARY;
            if (!str_contains(strtolower($phpBinary), 'fpm') && file_exists($phpBinary)) {
                $possiblePaths[] = $phpBinary;
            }
        }

        $envPhpBinary = $_ENV['PHP_BINARY'] ?? getenv('PHP_BINARY');
        if ($envPhpBinary && file_exists($envPhpBinary) && !str_contains(strtolower($envPhpBinary), 'fpm')) {
            $possiblePaths[] = $envPhpBinary;
        }

        $commonPaths = ['/usr/bin/php', '/usr/local/bin/php', '/opt/homebrew/bin/php'];
        foreach ($commonPaths as $path) {
            if (file_exists($path) && is_executable($path) && !str_contains($path, 'fpm')) {
                $possiblePaths[] = $path;
            }
        }

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path) && is_executable($path)) {
                if (str_contains(strtolower($path), 'fpm')) {
                    continue;
                }

                $testOutput = [];
                $testReturnCode = 0;
                @exec(escapeshellarg($path) . ' -v 2>/dev/null', $testOutput, $testReturnCode);
                if ($testReturnCode === 0 && !empty($testOutput[0])) {
                    if (str_contains($testOutput[0], 'cli')) {
                        $phpPath = $path;
                        return $phpPath;
                    }
                }
            }
        }

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path) && is_executable($path)) {
                if (str_contains(strtolower($path), 'fpm')) {
                    continue;
                }
                $phpPath = $path;
                return $phpPath;
            }
        }

        error_log('DeploymentExecutionService: Could not find PHP CLI executable, falling back to "php" command');
        $phpPath = 'php';
        return $phpPath;
    }

    public function storeDeploymentLog(string $deploymentId, string $output): void
    {
        $logDir = $this->getLogDirectory();
        $logPath = $this->getLogPath($deploymentId);

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logContent = "[{$timestamp}] Deployment Output:\n" . $output . "\n";

        if (file_exists($logPath) && filesize($logPath) > self::MAX_LOG_SIZE) {
            $this->rotateLog($logPath);
        }

        file_put_contents($logPath, $logContent, FILE_APPEND);
    }

    public function getDeploymentLog(string $deploymentId): ?string
    {
        $logPath = $this->getLogPath($deploymentId);

        if (!file_exists($logPath)) {
            return null;
        }

        $content = file_get_contents($logPath);
        return $content !== false ? $content : null;
    }

    public function deleteDeploymentLog(string $deploymentId): bool
    {
        $logPath = $this->getLogPath($deploymentId);

        if (file_exists($logPath)) {
            return unlink($logPath);
        }

        return true;
    }

    private function generateDeploymentId(string $command): string
    {
        $timestamp = time();
        $commandHash = substr(md5($command), 0, 8);
        return "deploy-{$timestamp}-{$commandHash}";
    }

    private function buildArgsString(array $args): string
    {
        if (empty($args)) {
            return '';
        }

        $parts = [];
        foreach ($args as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $parts[] = "--{$key}";
                }
            } else {
                $parts[] = sprintf("--%s=%s", $key, escapeshellarg((string)$value));
            }
        }

        return implode(' ', $parts);
    }

    private function sanitizeOutput(string $output): string
    {
        $sensitivePatterns = [
            '/api[_-]?token["\']?\s*[:=]\s*["\']?([a-zA-Z0-9_-]{20,})/i',
            '/password["\']?\s*[:=]\s*["\']?([^\s"\']+)/i',
            '/secret["\']?\s*[:=]\s*["\']?([a-zA-Z0-9_-]{20,})/i',
        ];

        $sanitized = $output;
        foreach ($sensitivePatterns as $pattern) {
            $sanitized = preg_replace($pattern, '[REDACTED]', $sanitized);
        }

        return $sanitized;
    }

    private function getLogDirectory(): string
    {
        return BASE_PATH . '/' . self::LOG_DIR;
    }

    private function getLogPath(string $deploymentId): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $deploymentId);
        return $this->getLogDirectory() . '/' . $safeId . '.log';
    }

    private function rotateLog(string $logPath): void
    {
        $backupPath = $logPath . '.old';
        if (file_exists($backupPath)) {
            @unlink($backupPath);
        }
        @rename($logPath, $backupPath);
    }
}
