<?php
// TestRunner.php
namespace Forge\Modules\ForgeTesting;

use Forge\Core\Helpers\App;

class TestRunner
{
    private const TEST_METHOD_PREFIX = 'test';
    private array $config;

    public function __construct()
    {
        $config = App::config()->get('forge_testing');
        $this->config = $config;
    }

    public function runTests(string $filter = ''): array
    {
        $results = [
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'failures' => []
        ];

        foreach ($this->discoverTests() as $testClass) {
            $reflection = new \ReflectionClass($testClass);

            foreach ($reflection->getMethods() as $method) {
                if ($this->isTestMethod($method)) {
                    $results['total']++;
                    $result = $this->runTest($testClass, $method);

                    if ($result['passed']) {
                        $results['passed']++;
                    } else {
                        $results['failed']++;
                        $results['failures'][] = $result;
                    }
                }
            }
        }

        return $results;
    }

    private function discoverTests(): array
    {
        $tests = [];
        $suffix = $this->config['test_suffix'];


        foreach ($this->config['test_directories'] as $dir) {
            $dir = BASE_PATH . '/' . ltrim($dir, '/');
            if (!is_dir($dir)) {
                error_log("Directory is not a valid directory: " . $dir);
                continue;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir)
            );

            foreach ($files as $file) {
                if ($file->isFile() && str_ends_with($file->getFilename(), $suffix)) {
                    $className = $this->getClassNameFromFile($file->getPathname());
                    if ($className) $tests[] = $className;
                }
            }
        }

        return $tests;
    }

    private function getClassNameFromFile(string $path): ?string
    {
        $contents = file_get_contents($path);
        $tokens = token_get_all($contents);
        $namespace = $class = '';

        foreach ($tokens as $index => $token) {
            if ($token[0] === T_NAMESPACE) {
                $namespace = $this->parseNamespace($tokens, $index);
            }
            if ($token[0] === T_CLASS) {
                $class = $this->parseClassName($tokens, $index);
            }
        }

        $fullClassName = $class ? ($namespace ? "$namespace\\$class" : $class) : null;

        return $fullClassName;
    }

    private function runTest(string $class, \ReflectionMethod $method): array
    {
        try {
            $instance = new $class();
            $instance->setUp();
            $method->invoke($instance);
            $instance->tearDown();

            return [
                'passed' => true,
                'class' => $class,
                'method' => $method->getName()
            ];
        } catch (\Throwable $e) {
            return [
                'passed' => false,
                'class' => $class,
                'method' => $method->getName(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }
    }

    private function isTestMethod(\ReflectionMethod $method): bool
    {
        return str_starts_with($method->getName(), self::TEST_METHOD_PREFIX) &&
            $method->isPublic() &&
            !$method->isStatic();
    }

    // Helper methods for parsing class names
    private function parseNamespace(array &$tokens, int &$index): string // Pass index by reference
    {
        $namespace = '';
        // Note: Not using next() here, controlling index directly based on initial index
        for ($i = $index + 1; $i < count($tokens); $i++) { // Start from the token *after* T_NAMESPACE
            $token = $tokens[$i];
            if (is_array($token)) { // Check if it's a token array
                if ($token[0] === T_STRING || $token[0] === T_NS_SEPARATOR) {
                    $namespace .= $token[1];
                } elseif ($token[0] === T_WHITESPACE) {
                    continue; // Ignore whitespace in namespace
                } else {
                    break; // Stop on other token types
                }
            } elseif ($token === ';') {
                break;
            } else {
                break; // Stop on non-token array
            }
        }
        return $namespace;
    }

    private function parseClassName(array &$tokens, int &$index): string // Pass index by reference
    {
        $class = '';
        // Note: Not using next() here, controlling index directly based on initial index
        for ($i = $index + 1; $i < count($tokens); $i++) { // Start from the token *after* T_CLASS
            $token = $tokens[$i];
            if (is_array($token)) { // Check if it's a token array
                if ($token[0] === T_STRING) {
                    $class .= $token[1];
                } elseif ($token[0] === T_WHITESPACE) {
                    continue; // Ignore whitespace in class name
                } else {
                    break; // Stop on other token types
                }
            } elseif ($token === '{' || $token === ' ') {
                break;
            } else {
                break; // Stop on non-token array
            }
        }
        return $class;
    }
}