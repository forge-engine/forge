<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Controllers\Hub;

use App\Modules\ForgeDeployment\Services\DeploymentHubService;
use App\Modules\ForgeDeployment\Services\DeploymentExecutionService;
use App\Modules\ForgeDeployment\Services\DeploymentConfigReader;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
#[Middleware('auth')]
#[Middleware('hub-permissions')]
final class DeploymentController
{
    use ControllerHelper;

    public function __construct(
        private readonly DeploymentHubService $deploymentHubService,
        private readonly DeploymentExecutionService $executionService,
        private readonly DeploymentConfigReader $configReader,
        private readonly Config $config
    ) {
    }

    #[Route("/hub/deployment")]
    public function index(Request $request): Response
    {
        $status = $this->deploymentHubService->getDeploymentStatus();
        $config = $this->deploymentHubService->getDeploymentConfig();
        $hasConfig = $this->deploymentHubService->hasConfig();
        $logs = $this->deploymentHubService->listDeploymentLogs();

        $data = [
            'title' => 'Deployment',
            'status' => $status,
            'config' => $config,
            'has_config' => $hasConfig,
            'config_path' => $this->deploymentHubService->getConfigPath(),
            'recent_logs' => array_slice($logs, 0, 10),
        ];

        return $this->view(view: "pages/hub/deployment", data: $data);
    }

    #[Route("/hub/deployment/status", "GET")]
    public function getStatus(Request $request): Response
    {
        $status = $this->deploymentHubService->getDeploymentStatus();

        return $this->jsonResponse([
            'success' => true,
            'status' => $status,
        ]);
    }

    #[Route("/hub/deployment/config", "GET")]
    public function getConfig(Request $request): Response
    {
        $config = $this->deploymentHubService->getDeploymentConfig();

        return $this->jsonResponse([
            'success' => true,
            'config' => $config,
            'has_config' => $this->deploymentHubService->hasConfig(),
            'config_path' => $this->deploymentHubService->getConfigPath(),
        ]);
    }

    #[Route("/hub/deployment/config", "POST")]
    public function saveConfig(Request $request): Response
    {
        $data = $request->json();
        $updateType = $data['update'] ?? null;

        if ($updateType === 'post_deployment_commands') {
            $commands = $data['post_deployment_commands'] ?? [];
            if (!is_array($commands)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid commands data',
                ], 400);
            }

            $currentConfig = $this->deploymentHubService->getRawDeploymentConfig();
            if ($currentConfig === null) {
                $currentConfig = [];
            }
            if (!isset($currentConfig['deployment'])) {
                $currentConfig['deployment'] = [];
            }
            $currentConfig['deployment']['post_deployment_commands'] = $commands;

            $result = $this->deploymentHubService->saveDeploymentConfig($currentConfig);
            if (!$result) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to save commands',
                ], 500);
            }

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Post-deployment commands saved successfully',
            ]);
        }

        if ($updateType === 'env_vars') {
            $envVars = $data['env_vars'] ?? [];
            if (!is_array($envVars)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid environment variables data',
                ], 400);
            }

            $currentConfig = $this->deploymentHubService->getRawDeploymentConfig();
            if ($currentConfig === null) {
                $currentConfig = [];
            }
            if (!isset($currentConfig['deployment'])) {
                $currentConfig['deployment'] = [];
            }
            $currentConfig['deployment']['env_vars'] = $envVars;

            $result = $this->deploymentHubService->saveDeploymentConfig($currentConfig);
            if (!$result) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to save environment variables',
                ], 500);
            }

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Environment variables saved successfully',
            ]);
        }

        $config = $data['config'] ?? null;

        if ($config === null || !is_array($config)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid configuration data',
            ], 400);
        }

        $errors = $this->deploymentHubService->validateConfig($config);
        if (!empty($errors)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Configuration validation failed',
                'errors' => $errors,
            ], 400);
        }

        $result = $this->deploymentHubService->saveDeploymentConfig($config);
        if (!$result) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to save configuration file',
            ], 500);
        }

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Configuration saved successfully',
        ]);
    }

    #[Route("/hub/deployment/deploy", "POST")]
    public function deploy(Request $request): Response
    {
        $data = $request->json();
        $args = $data['args'] ?? [];

        $result = $this->executionService->executeDeployment('forge-deployment:deploy', $args);

        return $this->jsonResponse([
            'success' => $result['success'],
            'deployment_id' => $result['deployment_id'],
            'message' => $result['success'] ? 'Deployment started successfully' : 'Deployment failed',
            'output' => $result['output'] ?? '',
        ], $result['success'] ? 200 : 500);
    }

    #[Route("/hub/deployment/deploy-app", "POST")]
    public function deployApp(Request $request): Response
    {
        $data = $request->json();
        $args = $data['args'] ?? [];

        $result = $this->executionService->executeDeployment('forge-deployment:deploy-app', $args);

        return $this->jsonResponse([
            'success' => $result['success'],
            'deployment_id' => $result['deployment_id'],
            'message' => $result['success'] ? 'Application deployment started successfully' : 'Application deployment failed',
            'output' => $result['output'] ?? '',
        ], $result['success'] ? 200 : 500);
    }

    #[Route("/hub/deployment/update", "POST")]
    public function update(Request $request): Response
    {
        $data = $request->json();
        $args = $data['args'] ?? [];

        $result = $this->executionService->executeDeployment('forge-deployment:update', $args);

        return $this->jsonResponse([
            'success' => $result['success'],
            'deployment_id' => $result['deployment_id'],
            'message' => $result['success'] ? 'Update deployment started successfully' : 'Update deployment failed',
            'output' => $result['output'] ?? '',
        ], $result['success'] ? 200 : 500);
    }

    #[Route("/hub/deployment/rollback", "POST")]
    public function rollback(Request $request): Response
    {
        $data = $request->json();
        $args = $data['args'] ?? [];

        $result = $this->executionService->executeDeployment('forge-deployment:rollback', $args);

        return $this->jsonResponse([
            'success' => $result['success'],
            'deployment_id' => $result['deployment_id'],
            'message' => $result['success'] ? 'Rollback started successfully' : 'Rollback failed',
            'output' => $result['output'] ?? '',
        ], $result['success'] ? 200 : 500);
    }

    #[Route("/hub/deployment/logs/{deploymentId}", "GET")]
    public function getLogs(Request $request, string $deploymentId): Response
    {
        $logs = $this->executionService->getDeploymentLog($deploymentId);

        if ($logs === null) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Deployment logs not found',
            ], 404);
        }

        return $this->jsonResponse([
            'success' => true,
            'logs' => $logs,
            'deployment_id' => $deploymentId,
        ]);
    }

    #[Route("/hub/deployment/secrets", "GET")]
    public function getSecrets(Request $request): Response
    {
        $secrets = [
            'digitalocean_api_token' => $this->maskSecret($this->config->get('forge_deployment.digitalocean.api_token', '')),
            'cloudflare_api_token' => $this->maskSecret($this->config->get('forge_deployment.cloudflare.api_token', '')),
        ];

        return $this->jsonResponse([
            'success' => true,
            'secrets' => $secrets,
        ]);
    }

    #[Route("/hub/deployment/secrets", "POST")]
    public function updateSecrets(Request $request): Response
    {
        $data = $request->json();
        $secrets = $data['secrets'] ?? [];

        if (isset($secrets['digitalocean_api_token'])) {
            $token = $secrets['digitalocean_api_token'];
            if ($token !== '••••••••' && !empty($token)) {
                $this->config->set('forge_deployment.digitalocean.api_token', $token);
                putenv('FORGE_DEPLOYMENT_DIGITALOCEAN_API_TOKEN=' . $token);
            }
        }

        if (isset($secrets['cloudflare_api_token'])) {
            $token = $secrets['cloudflare_api_token'];
            if ($token !== '••••••••' && !empty($token)) {
                $this->config->set('forge_deployment.cloudflare.api_token', $token);
                putenv('FORGE_DEPLOYMENT_CLOUDFLARE_API_TOKEN=' . $token);
            }
        }

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Secrets updated successfully',
        ]);
    }

    private function maskSecret(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return '••••••••';
    }

    private function generateConfigFile(array $config): string
    {
        $template = $this->configReader->generateConfigTemplate();

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

        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $subArray = $this->formatSubArray($v, $indent + 1);
                $lines[] = "{$spaces}    '{$k}' => {$subArray},";
            } elseif (is_bool($v)) {
                $lines[] = "{$spaces}    '{$k}' => " . ($v ? 'true' : 'false') . ',';
            } elseif (is_numeric($v)) {
                $lines[] = "{$spaces}    '{$k}' => {$v},";
            } elseif ($v === null) {
                $lines[] = "{$spaces}    '{$k}' => null,";
            } else {
                $escaped = addslashes((string)$v);
                $lines[] = "{$spaces}    '{$k}' => '{$escaped}',";
            }
        }

        $lines[] = "{$spaces}]";

        return implode("\n", $lines);
    }
}
