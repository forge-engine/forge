<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\CLICommand;
use App\Modules\ForgeTesting\TestRunner;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use DirectoryIterator;
use Forge\Traits\NamespaceHelper;

#[CLICommand(name: 'test', description: 'Run application tests')]
class TestCommand extends Command
{
    use OutputHelper;
    use NamespaceHelper;

    private const CACHE_FILE = BASE_PATH . '/storage/framework/cache/test_cache.php';
    private const CACHE_TTL = 3600; // 1 hour

    public function execute(array $args): int
    {
        $startTime = microtime(true);
        $options = $this->parseOptions($args);

        $testDirs = $this->getTestDirectories(
            $options['type'],
            $options['module']
        );

        if (empty($testDirs)) {
            $this->error('No test directories found');
            return 1;
        }

        $cache = $this->getValidatedCache($testDirs);
        $runner = new TestRunner($cache['classes'], $options['group']);

        $this->info("Running tests...\n");
        $results = $runner->runTests();

        $this->updateCache($cache['meta'], $testDirs);
        $this->renderExecutionTime($startTime);

        return $results['failed'] > 0 ? 1 : 0;
    }

    private function parseOptions(array $args): array
    {
        $options = [
            'type' => 'app',
            'module' => 'all',
            'group' => null,
        ];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                [$key, $value] = explode('=', substr($arg, 2)) + [1 => null];
                if (in_array($key, ['type', 'module', 'group'])) {
                    $options[$key] = $value;
                }
            }
        }

        if ($options['type'] === 'module' && $options['module'] === 'all') {
            $options['module'] = $this->getAllModules();
        }

        return $options;
    }

    private function getTestDirectories(string $type, string $module): array
    {
        return match ($type) {
            'app' => [BASE_PATH . '/app/tests/'],
            'engine' => [BASE_PATH . '/engine/tests/'],
            'module' => $this->getModuleTestDirs($module),
            default => [],
        };
    }

    private function getModuleTestDirs(string $module): array
    {
        $dirs = [];
        $modules = is_array($module) ? $module : [$module];

        foreach ($modules as $moduleName) {
            $pascalCase = $this->kebabToPascal($moduleName);
            $path = BASE_PATH . "/modules/{$pascalCase}/src/tests/";

            if (is_dir($path)) {
                $dirs[] = $path;
            }
        }

        return $dirs;
    }

    private function getAllModules(): array
    {
        $modules = [];
        $dir = new DirectoryIterator(BASE_PATH . '/modules/');

        foreach ($dir as $fileInfo) {
            if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                $modules[] = $this->pascalToKebab($fileInfo->getFilename());
            }
        }

        return $modules;
    }

    private function getValidatedCache(array $testDirs): array
    {
        $currentHashes = $this->getDirectoryHashes($testDirs);

        if (file_exists(self::CACHE_FILE)) {
            $cache = include self::CACHE_FILE;

            if ($cache['meta']['hashes'] === $currentHashes &&
                time() - $cache['meta']['timestamp'] < self::CACHE_TTL) {
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
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir)
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $hash .= md5_file($file->getRealPath());
                }
            }

            $hashes[$dir] = md5($hash);
        }
        return $hashes;
    }

    private function scanTestClasses(array $directories): array
    {
        $classes = [];
        foreach ($directories as $dir) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir)
            );

            foreach ($files as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
                    $className = $this->getClassNameFromFile($file->getPathname());
                    if ($className && class_exists($className)) {
                        $classes[] = $className;
                    }
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

    private function kebabToPascal(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $name)));
    }

    private function pascalToKebab(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
    }
}
