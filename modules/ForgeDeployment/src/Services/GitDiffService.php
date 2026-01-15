<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class GitDiffService
{
  public function __construct(
    private readonly ForgeIgnoreService $ignoreService
  ) {
    $this->ignoreService->load(BASE_PATH);
  }

  public function isGitRepository(): bool
  {
    $gitDir = BASE_PATH . '/.git';
    return is_dir($gitDir);
  }

  public function getCurrentCommitHash(): ?string
  {
    if (!$this->isGitRepository()) {
      return null;
    }

    $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git rev-parse HEAD 2>/dev/null';
    $output = [];
    $exitCode = 0;
    @exec($command, $output, $exitCode);

    if ($exitCode !== 0 || empty($output)) {
      return null;
    }

    return trim($output[0]);
  }

  public function getChangedFiles(?string $baseCommit = null, bool $includeUntracked = false): array
  {
    if (!$this->isGitRepository()) {
      return [];
    }

    $changedFiles = [];

    // If baseCommit is null, we're diffing against working tree
    if ($baseCommit === null) {
      // Get modified and staged files
      $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git diff --name-only --diff-filter=ACMRT 2>/dev/null';
      $output = [];
      @exec($command, $output);
      $changedFiles = array_merge($changedFiles, $output);

      // Get staged files
      $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git diff --cached --name-only --diff-filter=ACMRT 2>/dev/null';
      $output = [];
      @exec($command, $output);
      $changedFiles = array_merge($changedFiles, $output);

      // Include untracked files if requested
      if ($includeUntracked) {
        $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git ls-files --others --exclude-standard 2>/dev/null';
        $output = [];
        @exec($command, $output);
        $changedFiles = array_merge($changedFiles, $output);
      }
    } else {
      // Diff against specific commit
      $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git diff --name-only --diff-filter=ACMRT ' . escapeshellarg($baseCommit) . ' HEAD 2>/dev/null';
      $output = [];
      @exec($command, $output);
      $changedFiles = array_merge($changedFiles, $output);
    }

    // Remove duplicates
    $changedFiles = array_unique($changedFiles);
    $changedFiles = array_filter($changedFiles, fn($file) => $file !== '');

    // Filter out files that match .forgeignore patterns
    $filteredFiles = [];
    foreach ($changedFiles as $file) {
      $absolutePath = BASE_PATH . '/' . $file;

      // Check if file should be ignored
      if (!$this->ignoreService->shouldIgnore($absolutePath)) {
        $filteredFiles[] = $file;
      }
    }

    return array_values($filteredFiles);
  }

  public function getFirstCommitHash(): ?string
  {
    if (!$this->isGitRepository()) {
      return null;
    }

    $command = 'cd ' . escapeshellarg(BASE_PATH) . ' && git rev-list --max-parents=0 HEAD 2>/dev/null';
    $output = [];
    $exitCode = 0;
    @exec($command, $output, $exitCode);

    if ($exitCode !== 0 || empty($output)) {
      return null;
    }

    return trim($output[0]);
  }
}
