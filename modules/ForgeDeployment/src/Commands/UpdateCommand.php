<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Services\DeploymentConfigReader;
use App\Modules\ForgeDeployment\Services\DeploymentService;
use App\Modules\ForgeDeployment\Services\DeploymentStateService;
use App\Modules\ForgeDeployment\Services\SshService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;

#[Cli(
  command: 'forge-deployment:update',
  description: 'Push changes to existing deployed server and run post-deployment commands',
  usage: 'forge-deployment:update [--skip-commands]',
  examples: [
    'forge-deployment:update',
    'forge-deployment:update --skip-commands',
  ]
)]
final class UpdateCommand extends Command
{
  use OutputHelper;

  #[Arg(name: 'skip-commands', description: 'Skip post-deployment commands', required: false)]
  private bool $skipCommands = false;

  public function __construct(
    private readonly DeploymentStateService $stateService,
    private readonly DeploymentService $deploymentService,
    private readonly DeploymentConfigReader $configReader,
    private readonly SshService $sshService
  ) {
  }

  public function execute(array $args): int
  {
    try {
      $state = $this->stateService->load();

      if ($state === null) {
        $this->error('No deployment state found. Run forge-deployment:deploy to start a new deployment.');
        return 1;
      }

      if ($state->serverIp === null || $state->domain === null) {
        $this->error('Invalid deployment state: missing server IP or domain.');
        return 1;
      }

      if (!$this->stateService->validate($state)) {
        $this->error('Cannot update: Server is not accessible. Please check the server status.');
        return 1;
      }

      $this->info('Updating deployment...');
      $this->info("Server IP: {$state->serverIp}");
      $this->info("Domain: {$state->domain}");

      $fileConfig = $this->configReader->readConfig(null);
      $deploymentConfig = null;

      if ($fileConfig !== null && isset($fileConfig['deployment'])) {
        $deploymentConfig = \App\Modules\ForgeDeployment\Dto\DeploymentConfig::fromArray($fileConfig['deployment']);
      } else {
        $this->error('No deployment configuration found. Please create a forge-deployment.php file.');
        return 1;
      }

      $sshPrivateKeyPath = $this->expandPath($state->sshKeyPath ?? '~/.ssh/id_rsa');

      $this->info('Connecting to server...');
      $connected = $this->sshService->connect(
        $state->serverIp,
        22,
        'root',
        $sshPrivateKeyPath,
        $sshPrivateKeyPath . '.pub'
      );

      if (!$connected) {
        $this->error('Failed to connect to server. Please check your SSH key and server accessibility.');
        return 1;
      }

      $remotePath = '/var/www/' . $state->domain;

      $outputCallback = function (string $line) {
        if (trim($line) !== '') {
          $this->line('      ' . trim($line));
        }
      };

      $errorCallback = function (string $line) {
        if (trim($line) !== '') {
          $this->error('      ' . trim($line));
        }
      };

      $this->info('Uploading project files...');
      $this->deploymentService->deploy(
        BASE_PATH,
        $remotePath,
        $deploymentConfig->commands,
        $deploymentConfig->envVars
      );
      $this->success('Project files uploaded');

      if (!$this->skipCommands && !empty($deploymentConfig->postDeploymentCommands)) {
        $this->info('Running post-deployment commands...');
        $this->deploymentService->runPostDeploymentCommands($remotePath, $deploymentConfig->postDeploymentCommands);
        $this->success('Post-deployment commands completed');
      } else {
        $this->info('Skipping post-deployment commands');
      }

      $state = $state->markStepCompleted('project_uploaded');
      $this->stateService->save($state);

      $this->success('Update completed successfully!');
      $this->line("Server IP: {$state->serverIp}");
      $this->line("Domain: {$state->domain}");

      return 0;
    } catch (\Exception $e) {
      $this->error('Update failed: ' . $e->getMessage());
      return 1;
    }
  }

  private function expandPath(string $path): string
  {
    if (str_starts_with($path, '~/')) {
      $home = $_SERVER['HOME'] ?? getenv('HOME') ?? '';
      if ($home !== '') {
        return $home . substr($path, 1);
      }
    }
    return $path;
  }
}
