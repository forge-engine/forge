<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Commands;

use Exception;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\CliGenerator;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;
use Forge\Traits\StringHelper;

#[Cli(
  command: 'forgewire:island',
  description: 'Create a new ForgeWire island',
  usage: 'forgewire:island [--type=app|module] [--module=ModuleName] [--name=IslandName] [--kind=component|page]',
  examples: [
    'forgewire:island --type=app --name=interactive-counter --kind=component',
    'forgewire:island --type=app --name=pos/cart --kind=component',
    'forgewire:island --type=app --name=admin/dashboard --kind=page',
    'forgewire:island   (starts wizard)',
  ]
)]
final class GenerateIslandCommand extends Command
{
  use StringHelper;
  use CliGenerator;
  use OutputHelper;
  use Wizard;

  #[Arg(name: 'type', description: 'app or module', validate: 'app|module')]
  private string $type;

  #[Arg(name: 'module', description: 'Module name when type=module', required: false)]
  private ?string $module = null;

  #[Arg(name: 'name', description: 'Island name (e.g., interactive-counter, pos/cart, admin/dashboard)', validate: '/^[\w\/\s-]+$/')]
  private string $name;

  #[Arg(name: 'kind', description: 'Is this a component or page?', validate: 'component|page', required: false)]
  private ?string $kind = null;

  #[Arg(
    name: 'path',
    description: 'Optional subfolder',
    default: '',
    required: false
  )]
  private string $path = '';

  /**
   * @throws Exception
   */
  public function execute(array $args): int
  {
    $this->wizard($args);

    if ($this->type === 'module' && !$this->module) {
      $this->error('--module=Name required when --type=module');
      return 1;
    }

    if (!$this->kind) {
      $this->prompt("\033[1;36mIs this a component or page? (component|page):\033[0m ");
      $input = trim(fgets(STDIN));
      while ($input !== 'component' && $input !== 'page') {
        $this->error('Invalid choice. Please enter "component" or "page".');
        $this->prompt("\033[1;36mIs this a component or page? (component|page):\033[0m ");
        $input = trim(fgets(STDIN));
      }
      $this->kind = $input;
    }

    $islandFile = $this->islandPath();

    $parts = explode('/', $this->name);
    $normalizedParts = array_map(fn($part) => $this->slugify($part), $parts);
    $normalizedName = implode('/', $normalizedParts);

    $tokens = [
      '{{ islandName }}' => $normalizedName,
    ];

    $stubPath = $this->islandStubPath();
    $this->generateFromStubPath($stubPath, $islandFile, $tokens);
    return 0;
  }

  private function islandPath(): string
  {
    $parts = explode('/', $this->name);

    if (count($parts) === 1) {
      $folder = '';
      $filename = $this->slugify($parts[0]);
    } else {
      $filenamePart = array_pop($parts);
      $filename = $this->slugify($filenamePart);
      $normalizedParts = array_map(fn($part) => $this->slugify($part), $parts);
      $folder = implode('/', $normalizedParts);
    }

    if ($this->kind === 'component') {
      $baseDir = $this->type === 'app'
        ? BASE_PATH . '/app/resources/components'
        : BASE_PATH . "/modules/{$this->module}/src/Resources/components";
    } else {
      $baseDir = $this->type === 'app'
        ? BASE_PATH . '/app/resources/views/pages'
        : BASE_PATH . "/modules/{$this->module}/src/Resources/views/pages";
    }

    if ($this->path !== '') {
      $baseDir .= '/' . $this->normalizePath($this->path);
    }

    if ($folder !== '') {
      $baseDir .= '/' . $folder;
    }

    $fullPath = $baseDir . '/' . $filename . '.php';

    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }

    return $fullPath;
  }

  private function normalizePath(string $path): string
  {
    $path = trim($path);
    if ($path === '')
      return '';
    return trim(str_replace(['\\', '//'], '/', $path), '/');
  }

  private function islandStubPath(): string
  {
    return BASE_PATH . '/modules/ForgeWire/src/resources/stubs/island.stub';
  }

  private function generateFromStubPath(string $stubPath, string $targetPath, array $tokens, bool $force = false): void
  {
    if (is_file($targetPath) && !$force) {
      $this->error("File exists: $targetPath  (--force to overwrite)");
      exit(1);
    }

    $dir = dirname($targetPath);
    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }

    $content = file_get_contents($stubPath);
    $content = strtr($content, $tokens);

    file_put_contents($targetPath, $content);
    $this->success("Created: $targetPath");
  }
}
