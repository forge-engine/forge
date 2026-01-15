<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Commands;

use Forge\CLI\Command;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;

#[Cli(
  command: 'forgewire:minify',
  description: 'Generate minified production version of forgewire.js',
  usage: 'forgewire:minify [--input=JS_PATH] [--output=JS_PATH]',
  examples: [
    'forgewire:minify',
    'forgewire:minify --input=modules/ForgeWire/src/Resources/assets/js/forgewire.js --output=modules/ForgeWire/src/Resources/assets/js/forgewire.min.js'
  ]
)]
final class MinifyForgeWireCommand extends Command
{
  use OutputHelper;
  use Wizard;

  #[Arg(
    name: 'input',
    description: 'Input JS file path (default: modules/ForgeWire/src/Resources/assets/js/forgewire.js)',
    required: false
  )]
  private ?string $inputJs = null;

  #[Arg(
    name: 'output',
    description: 'Output JS file path (default: modules/ForgeWire/src/Resources/assets/js/forgewire.min.js)',
    required: false
  )]
  private ?string $outputJs = null;

  public function execute(array $args): int
  {
    $this->wizard($args);

    $input = $this->inputJs ?? BASE_PATH . '/modules/ForgeWire/src/Resources/assets/js/forgewire.js';
    $output = $this->outputJs ?? BASE_PATH . '/modules/ForgeWire/src/Resources/assets/js/forgewire.min.js';

    if (!file_exists($input)) {
      $this->error("Input file not found: {$input}", 'MinifyForgeWire');
      return 1;
    }

    $this->info("Reading source file: {$input}", 'MinifyForgeWire');
    $source = file_get_contents($input);

    if ($source === false) {
      $this->error("Failed to read input file: {$input}", 'MinifyForgeWire');
      return 1;
    }

    $this->info("Minifying JavaScript...", 'MinifyForgeWire');
    $minified = $this->minifyJavaScript($source);

    $outputDir = dirname($output);
    if (!is_dir($outputDir)) {
      mkdir($outputDir, 0755, true);
    }

    if (file_put_contents($output, $minified) === false) {
      $this->error("Failed to write output file: {$output}", 'MinifyForgeWire');
      return 1;
    }

    $originalSize = strlen($source);
    $minifiedSize = strlen($minified);
    $reduction = round((1 - ($minifiedSize / $originalSize)) * 100, 2);

    $this->success("Minified successfully!", 'MinifyForgeWire');
    $this->line("  Original: " . $this->formatBytes($originalSize));
    $this->line("  Minified: " . $this->formatBytes($minifiedSize));
    $this->line("  Reduction: {$reduction}%");
    $this->line("  Output: {$output}");

    return 0;
  }

  private function minifyJavaScript(string $source): string
  {
    $minified = $source;
    $minified = preg_replace('/(?<!:|\/)\/\/[^\r\n]*/', '', $minified);
    $minified = preg_replace('/\/\*(?!\!)[^*]*\*+(?:[^*\/][^*]*\*+)*\//s', '', $minified);
    $minified = preg_replace('/\s*([=+\-*\/%<>!&|?:;,])\s*/', '$1', $minified);
    $minified = preg_replace('/\s*([{}()\[\]])\s*/', '$1', $minified);
    $minified = preg_replace('/\b(if|else|for|while|function|return|var|let|const|async|await|try|catch|finally|throw|new|typeof|instanceof|in|of)\s+/', '$1 ', $minified);
    $minified = preg_replace('/;\s*}/', '}', $minified);
    $minified = preg_replace('/[ \t]+/', ' ', $minified);
    $minified = preg_replace('/[\r\n]+/', '', $minified);
    $minified = trim($minified);

    return $minified;
  }

  private function formatBytes(int $bytes): string
  {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
  }
}
