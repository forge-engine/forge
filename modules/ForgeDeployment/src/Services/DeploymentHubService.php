<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use App\Modules\ForgeDeployment\Dto\DeploymentState;
use Forge\Core\DI\Attributes\Service;

#[Service]
final class DeploymentHubService
{
    private const SENSITIVE_KEYS = [
        'api_token',
        'password',
        'secret',
        'key',
        'token',
        'ssh_key_path',
        'private_key',
        'passphrase',
    ];

    public function __construct(
        private readonly DeploymentStateService $stateService,
        private readonly DeploymentConfigReader $configReader
    ) {
    }

    public function getDeploymentState(): ?DeploymentState
    {
        return $this->stateService->load();
    }

    public function getDeploymentConfig(?string $configPath = null): ?array
    {
        $config = $this->configReader->readConfig($configPath);
        if ($config === null) {
            return null;
        }

        return $this->maskSecrets($config);
    }

    public function getRawDeploymentConfig(?string $configPath = null): ?array
    {
        return $this->configReader->readConfig($configPath);
    }

    public function getDeploymentStatus(): array
    {
        $state = $this->stateService->load();

        if ($state === null) {
            return [
                'has_state' => false,
                'message' => 'No deployment state found',
            ];
        }

        $isAccessible = $this->stateService->validate($state);

        $allSteps = [
            'server_created',
            'ssh_connected',
            'system_provisioned',
            'php_installed',
            'database_installed',
            'nginx_installed',
            'project_uploaded',
            'site_configured',
            'dns_configured',
            'ssl_configured',
            'post_deployment_completed',
        ];

        $completedSteps = $state->completedSteps;
        $remainingSteps = array_diff($allSteps, $completedSteps);

        return [
            'has_state' => true,
            'server_ip' => $state->serverIp,
            'server_id' => $state->serverId,
            'domain' => $state->domain,
            'ssh_key_path' => $state->sshKeyPath ? $this->maskPath($state->sshKeyPath) : null,
            'completed_steps' => $completedSteps,
            'completed_steps_count' => count($completedSteps),
            'remaining_steps' => array_values($remainingSteps),
            'remaining_steps_count' => count($remainingSteps),
            'current_step' => $state->currentStep,
            'last_updated' => $state->lastUpdated,
            'last_deployed_commit' => $state->lastDeployedCommit,
            'config' => $state->config,
            'is_accessible' => $isAccessible,
            'progress_percentage' => count($allSteps) > 0 ? round((count($completedSteps) / count($allSteps)) * 100) : 0,
        ];
    }

    public function getDeploymentLogs(string $deploymentId): ?string
    {
        $logPath = $this->getLogPath($deploymentId);

        if (!file_exists($logPath)) {
            return null;
        }

        $content = file_get_contents($logPath);
        return $content !== false ? $content : null;
    }

    public function listDeploymentLogs(): array
    {
        $logDir = $this->getLogDirectory();

        if (!is_dir($logDir)) {
            return [];
        }

        $logs = [];
        $files = glob($logDir . '/*.log');

        foreach ($files as $file) {
            $basename = basename($file, '.log');
            $logs[] = [
                'id' => $basename,
                'path' => $file,
                'size' => filesize($file),
                'modified' => filemtime($file),
            ];
        }

        usort($logs, fn($a, $b) => $b['modified'] <=> $a['modified']);

        return $logs;
    }

    public function validateConfig(array $config): array
    {
        $errors = [];

        if (isset($config['server'])) {
            $server = $config['server'];
            if (empty($server['name'])) {
                $errors[] = 'Server name is required';
            }
            if (empty($server['region'])) {
                $errors[] = 'Server region is required';
            }
            if (empty($server['size'])) {
                $errors[] = 'Server size is required';
            }
            if (empty($server['image'])) {
                $errors[] = 'Server image is required';
            }
        }

        if (isset($config['provision'])) {
            $provision = $config['provision'];
            if (empty($provision['php_version'])) {
                $errors[] = 'PHP version is required';
            }
            if (empty($provision['database_type'])) {
                $errors[] = 'Database type is required';
            }
        }

        if (isset($config['deployment'])) {
            $deployment = $config['deployment'];
            if (empty($deployment['domain'])) {
                $errors[] = 'Domain is required';
            }
        }

        return $errors;
    }

    public function maskSecrets(array $config): array
    {
        $masked = [];

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $masked[$key] = $this->maskSecrets($value);
            } elseif ($this->isSensitiveKey($key)) {
                $masked[$key] = $this->maskValue($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    public function hasConfig(): bool
    {
        return $this->configReader->hasConfig();
    }

    public function getConfigPath(): ?string
    {
        $projectRoot = BASE_PATH;
        $configFile = $projectRoot . '/forge-deployment.php';

        if (file_exists($configFile)) {
            return $configFile;
        }

        $configFileAlt = $projectRoot . '/deployment.php';
        if (file_exists($configFileAlt)) {
            return $configFileAlt;
        }

        return null;
    }

    public function saveDeploymentConfig(array $config): bool
    {
        $configPath = $this->getConfigPath();
        if ($configPath === null) {
            $configPath = BASE_PATH . '/forge-deployment.php';
        }

        $configContent = $this->generateConfigFile($config);

        if (empty($configContent)) {
            return false;
        }

        $dir = dirname($configPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                return false;
            }
        }

        if (!is_writable($dir)) {
            return false;
        }

        if (file_exists($configPath) && !is_writable($configPath)) {
            return false;
        }

        $result = @file_put_contents($configPath, $configContent, LOCK_EX);

        if ($result === false) {
            return false;
        }

        if (!file_exists($configPath)) {
            return false;
        }

        $writtenContent = @file_get_contents($configPath);
        if ($writtenContent === false) {
            return false;
        }

        if (strlen($writtenContent) !== strlen($configContent)) {
            return false;
        }

        return true;
    }

    private function generateConfigFile(array $config): string
    {
        $server = $config['server'] ?? [];
        $provision = $config['provision'] ?? [];
        $deployment = $config['deployment'] ?? [];

        $serverConfig = $this->formatArray($server, 'server');
        $provisionConfig = $this->formatArray($provision, 'provision');
        $deploymentConfig = $this->formatArray($deployment, 'deployment');

        return <<<PHP
<?php

declare(strict_types=1);

return [
{$serverConfig}
{$provisionConfig}
{$deploymentConfig}
];
PHP;
    }

    private function formatArray(array $data, string $key): string
    {
        if (empty($data)) {
            return "    '{$key}' => [],";
        }

        $lines = ["    '{$key}' => ["];

        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $subArray = $this->formatSubArray($v, 2);
                $lines[] = "        '{$k}' => {$subArray},";
            } elseif (is_bool($v)) {
                $lines[] = "        '{$k}' => " . ($v ? 'true' : 'false') . ',';
            } elseif (is_numeric($v)) {
                $lines[] = "        '{$k}' => {$v},";
            } elseif ($v === null) {
                $lines[] = "        '{$k}' => null,";
            } else {
                $escaped = addslashes((string)$v);
                $lines[] = "        '{$k}' => '{$escaped}',";
            }
        }

        $lines[] = '    ],';

        return implode("\n", $lines);
    }

    private function formatSubArray(array $data, int $indent): string
    {
        if (empty($data)) {
            return '[]';
        }

        $spaces = str_repeat(' ', $indent * 4);
        $lines = ['['];

        $keys = array_keys($data);
        $isNumeric = true;
        foreach ($keys as $i => $key) {
            if (!is_int($key) || $key !== $i) {
                $isNumeric = false;
                break;
            }
        }

        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $subArray = $this->formatSubArray($v, $indent + 1);
                if ($isNumeric) {
                    $lines[] = "{$spaces}    {$subArray},";
                } else {
                    $lines[] = "{$spaces}    '{$k}' => {$subArray},";
                }
            } elseif (is_bool($v)) {
                $value = $v ? 'true' : 'false';
                if ($isNumeric) {
                    $lines[] = "{$spaces}    {$value},";
                } else {
                    $lines[] = "{$spaces}    '{$k}' => {$value},";
                }
            } elseif (is_numeric($v)) {
                if ($isNumeric) {
                    $lines[] = "{$spaces}    {$v},";
                } else {
                    $lines[] = "{$spaces}    '{$k}' => {$v},";
                }
            } elseif ($v === null) {
                if ($isNumeric) {
                    $lines[] = "{$spaces}    null,";
                } else {
                    $lines[] = "{$spaces}    '{$k}' => null,";
                }
            } else {
                $escaped = addslashes((string)$v);
                if ($isNumeric) {
                    $lines[] = "{$spaces}    '{$escaped}',";
                } else {
                    $lines[] = "{$spaces}    '{$k}' => '{$escaped}',";
                }
            }
        }

        $lines[] = "{$spaces}]";

        return implode("\n", $lines);
    }

    private function isSensitiveKey(mixed $key): bool
    {
        if (!is_string($key) && !is_int($key)) {
            return false;
        }

        $keyLower = strtolower((string)$key);

        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($keyLower, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    private function maskValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $length = strlen($value);
        if ($length <= 4) {
            return '••••';
        }

        return '••••••••';
    }

    private function maskPath(string $path): string
    {
        if (strlen($path) <= 20) {
            return '••••/' . basename($path);
        }

        $parts = explode('/', $path);
        $filename = array_pop($parts);
        $maskedParts = array_map(fn() => '••••', array_slice($parts, -2));

        return implode('/', $maskedParts) . '/' . $filename;
    }

    private function getLogDirectory(): string
    {
        $dir = BASE_PATH . '/storage/framework/deployments';

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function getLogPath(string $deploymentId): string
    {
        return $this->getLogDirectory() . '/' . basename($deploymentId) . '.log';
    }
}
