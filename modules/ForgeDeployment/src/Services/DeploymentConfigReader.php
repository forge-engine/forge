<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class DeploymentConfigReader
{
  private const CONFIG_FILE = 'forge-deployment.php';
  private const CONFIG_FILE_ALT = 'deployment.php';

  public function readConfig(?string $configPath = null): ?array
  {
    if ($configPath !== null && file_exists($configPath)) {
      return $this->loadConfigFile($configPath);
    }

    $projectRoot = BASE_PATH;
    $configFile = $projectRoot . '/' . self::CONFIG_FILE;
    if (file_exists($configFile)) {
      return $this->loadConfigFile($configFile);
    }

    $configFileAlt = $projectRoot . '/' . self::CONFIG_FILE_ALT;
    if (file_exists($configFileAlt)) {
      return $this->loadConfigFile($configFileAlt);
    }

    return null;
  }

  public function hasConfig(?string $configPath = null): bool
  {
    if ($configPath !== null && file_exists($configPath)) {
      return true;
    }

    $projectRoot = BASE_PATH;
    return file_exists($projectRoot . '/' . self::CONFIG_FILE) ||
      file_exists($projectRoot . '/' . self::CONFIG_FILE_ALT);
  }

  private function loadConfigFile(string $path): ?array
  {
    if (!file_exists($path) || !is_readable($path)) {
      return null;
    }

    $config = require $path;
    if (!is_array($config)) {
      return null;
    }

    return $this->normalizeConfig($config);
  }

  private function normalizeConfig(array $config): array
  {
    $normalized = [
      'server' => $config['server'] ?? [],
      'provision' => $config['provision'] ?? [],
      'deployment' => $config['deployment'] ?? [],
    ];

    return $normalized;
  }

  public function getServerConfig(array $config): ?array
  {
    return $config['server'] ?? null;
  }

  public function getProvisionConfig(array $config): ?array
  {
    return $config['provision'] ?? null;
  }

  public function getDeploymentConfig(array $config): ?array
  {
    return $config['deployment'] ?? null;
  }

  public function generateConfigTemplate(): string
  {
    return <<<'PHP'
<?php

declare(strict_types=1);

return [
    'server' => [
        'name' => 'my-app-server',
        'region' => 'nyc1',
        'size' => 's-1vcpu-1gb',
        'image' => 'ubuntu-22-04-x64',
        'ssh_key_path' => null,
    ],

    'provision' => [
        'php_version' => '8.4',
        'database_type' => 'mysql',
        'database_version' => '8.0',
        'database_name' => 'forge_app',
        'database_user' => 'forge_user',
        'database_password' => 'secret',
    ],

    'deployment' => [
        'domain' => 'example.com',
        'ssl_email' => 'admin@example.com',
        'commands' => [
        ],
        'post_deployment_commands' => [
            'cache:flush',
            'migrate',
        ],
        'env_vars' => [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
        ],
    ],
];
PHP;
  }
}
