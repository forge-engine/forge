<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Commands;

use App\Modules\ForgeDeployment\Services\DeploymentConfigReader;
use App\Modules\ForgeDeployment\Services\DeploymentService;
use App\Modules\ForgeDeployment\Services\DeploymentStateService;
use App\Modules\ForgeDeployment\Services\GitDiffService;
use App\Modules\ForgeDeployment\Services\IncrementalUploadService;
use App\Modules\ForgeDeployment\Services\SshService;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;

#[Cli(
  command: 'forge-deployment:update',
  description: 'Push changes to existing deployed server and run post-deployment commands',
  usage: 'forge-deployment:update [--skip-commands] [--working-tree] [--force-full]',
  examples: [
    'forge-deployment:update',
    'forge-deployment:update --skip-commands',
    'forge-deployment:update --working-tree',
    'forge-deployment:update --force-full',
  ]
)]
final class UpdateCommand extends Command
{
  use OutputHelper;

  #[Arg(name: 'skip-commands', description: 'Skip post-deployment commands', required: false)]
  private bool $skipCommands = false;

  #[Arg(name: 'working-tree', description: 'Diff against working tree (uncommitted changes)', required: false)]
  private bool $workingTree = false;

  #[Arg(name: 'force-full', description: 'Force full deployment instead of incremental', required: false)]
  private bool $forceFull = false;

  public function __construct(
    private readonly DeploymentStateService $stateService,
    private readonly DeploymentService $deploymentService,
    private readonly DeploymentConfigReader $configReader,
    private readonly SshService $sshService,
    private readonly GitDiffService $gitDiffService,
    private readonly IncrementalUploadService $incrementalUploadService
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

      // Determine if we should use incremental upload or full deployment
      $useIncremental = !$this->forceFull && $this->gitDiffService->isGitRepository();

      if ($useIncremental) {
        // Use incremental upload with git diff
        $this->info('Checking for changed files...');

        $baseCommit = null;
        if (!$this->workingTree) {
          // Use last deployed commit as baseline
          $baseCommit = $state->lastDeployedCommit;

          // If no previous commit, try to use first commit or HEAD~1
          if ($baseCommit === null) {
            $firstCommit = $this->gitDiffService->getFirstCommitHash();
            if ($firstCommit !== null) {
              $baseCommit = $firstCommit;
              $this->info("No previous deployment commit found, using first commit as baseline");
            } else {
              // Fallback to HEAD~1 if available
              $this->info("Using HEAD~1 as baseline (no previous deployment commit)");
              $baseCommit = 'HEAD~1';
            }
          }
        }

        $changedFiles = $this->gitDiffService->getChangedFiles($baseCommit, $this->workingTree);

        if (empty($changedFiles)) {
          $this->info('No changes detected. Nothing to upload.');

          // Still update the commit hash if we're using commit-based diff
          if (!$this->workingTree) {
            $currentCommit = $this->gitDiffService->getCurrentCommitHash();
            if ($currentCommit !== null) {
              $state = $state->withLastDeployedCommit($currentCommit);
              $this->stateService->save($state);
            }
          }

          $this->success('Update completed (no changes to deploy)');
          return 0;
        }

        $this->info('Found ' . count($changedFiles) . ' changed file(s)');

        $progressCallback = function (string $message) {
          $this->line('  ' . $message);
        };

        $this->info('Uploading changed files...');
        $this->incrementalUploadService->uploadChangedFiles(
          $changedFiles,
          BASE_PATH,
          $remotePath,
          $progressCallback
        );
        $this->success('Changed files uploaded');

        // Update the commit hash
        if (!$this->workingTree) {
          $currentCommit = $this->gitDiffService->getCurrentCommitHash();
          if ($currentCommit !== null) {
            $state = $state->withLastDeployedCommit($currentCommit);
          }
        }
      } else {
        // Fallback to full deployment
        if ($this->forceFull) {
          $this->info('Force full deployment requested...');
        } else {
          $this->warning('Not a git repository. Falling back to full deployment...');
        }

        $this->info('Uploading project files...');
        $this->deploymentService->deploy(
          BASE_PATH,
          $remotePath,
          $deploymentConfig->commands,
          $deploymentConfig->envVars
        );
        $this->success('Project files uploaded');

        // Update commit hash if it's a git repo
        if ($this->gitDiffService->isGitRepository()) {
          $currentCommit = $this->gitDiffService->getCurrentCommitHash();
          if ($currentCommit !== null) {
            $state = $state->withLastDeployedCommit($currentCommit);
          }
        }
      }

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
