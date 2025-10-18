<?php

declare(strict_types=1);

namespace App\Modules\ForgeTesting\Commands;

use App\Modules\ForgeTesting\Services\TestRunnerService;
use Forge\CLI\Command;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;
use Forge\Traits\NamespaceHelper;

#[Cli(
    command: 'test',
    description: 'Run application tests',
    usage: 'test [--type=TYPE] [--module=MODULE] [--group=GROUP]',
    examples: [
        'test',
        'test --type=module --module=users',
        'test --group=unit',
        'test --type=engine'
    ]
)]
final class TestCommand extends Command
{
    use OutputHelper;
    use Wizard;
    use NamespaceHelper;

    private const CACHE_FILE = BASE_PATH . '/storage/framework/cache/test_cache.php';
    private const CACHE_TTL = 3600;

    #[Arg(
        name: 'type',
        description: 'Type of tests: app, engine, module',
        default: 'app',
        required: false
    )]
    private string $type;

    #[Arg(
        name: 'module',
        description: 'Module(s) to test (default: all)',
        default: 'all',
        required: false
    )]
    private string|array $module;

    #[Arg(
        name: 'group',
        description: 'Filter tests by group (optional)',
        required: false
    )]
    private ?string $group = null;

    public function __construct(private TestRunnerService $testRunnerService)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        $startTime = microtime(true);

        $testDirs = $this->getTestDirectories($this->type, $this->module);
        if (empty($testDirs)) {
            $this->error('No test directories found');
            return 1;
        }

        $cache = $this->getValidatedCache($testDirs);

        $this->testRunnerService
            ->setTestClasses($cache['classes'])
            ->setGroupFilter($this->group);

        $this->info("Running tests...\n");
        $results = $this->testRunnerService->runTests();

        $this->updateCache($cache['meta'], $testDirs);
        $this->renderExecutionTime($startTime);

        return $results['failed'] > 0 ? 1 : 0;
    }

    private function getTestDirectories(string $type, string|array $module): array
    {
        return match ($type) {
            'app' => [BASE_PATH . '/app/tests/'],
            'engine' => [BASE_PATH . '/engine/tests/'],
            'module' => $this->getModuleTestDirs($module),
            default => [],
        };
    }

    private function getModuleTestDirs(string|array $module): array
    {
        $dirs = [];
        $modules = is_array($module) ? $module : [$module];

        foreach ($modules as $moduleName) {
            $pascalCase = $this->kebabToPascal($moduleName);
            $path = BASE_PATH . "/modules/{$pascalCase}/src/tests/";
            if (is_dir($path)) $dirs[] = $path;
        }

        return $dirs;
    }

    private function kebabToPascal(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $name)));
    }

    private function getValidatedCache(array $testDirs): array
    {
        $currentHashes = $this->getDirectoryHashes($testDirs);

        if (file_exists(self::CACHE_FILE)) {
            $cache = include self::CACHE_FILE;
            if ($cache['meta']['hashes'] === $currentHashes &&
                time() - $cache['meta']['timestamp'] < self::CACHE_TTL
            ) {
                return $cache;
            }
        }

        return [
            'meta' => ['hashes' => $currentHashes, 'timestamp' => time()],
            'classes' => $this->scanTestClasses($testDirs)
        ];
    }

    private function getDirectoryHashes(array $directories): array
    {
        $hashes = [];
        foreach ($directories as $dir) {
            $hash = '';
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file) {
                if ($file->isFile()) $hash .= md5_file($file->getRealPath());
            }
            $hashes[$dir] = md5($hash);
        }
        return $hashes;
    }

    private function scanTestClasses(array $directories): array
    {
        $classes = [];
        foreach ($directories as $dir) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
                    $className = $this->getClassNameFromFile($file->getPathname());
                    if ($className && class_exists($className)) $classes[] = $className;
                }
            }
        }
        return array_unique($classes);
    }

    private function updateCache(array $meta, array $testDirs): void
    {
        $cacheContent = "<?php\n\nreturn " . var_export([
                'meta' => $meta,
                'classes' => $this->scanTestClasses($testDirs)
            ], true) . ';';

        file_put_contents(self::CACHE_FILE, $cacheContent);
    }

    private function renderExecutionTime(float $startTime): void
    {
        $duration = number_format(microtime(true) - $startTime, 2);
        $this->comment("\nTests completed in {$duration}s");
    }

    private function getAllModules(): array
    {
        $modules = [];
        foreach (new \DirectoryIterator(BASE_PATH . '/modules/') as $fileInfo) {
            if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                $modules[] = $this->pascalToKebab($fileInfo->getFilename());
            }
        }
        return $modules;
    }

    private function pascalToKebab(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
    }
}