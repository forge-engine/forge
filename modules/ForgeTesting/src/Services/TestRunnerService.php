<?php

declare(strict_types=1);

namespace App\Modules\ForgeTesting\Services;

use App\Modules\ForgeTesting\Attributes\AfterEach;
use App\Modules\ForgeTesting\Attributes\BeforeEach;
use App\Modules\ForgeTesting\Attributes\DataProvider;
use App\Modules\ForgeTesting\Attributes\Depends;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Incomplete;
use App\Modules\ForgeTesting\Attributes\Skip;
use App\Modules\ForgeTesting\Attributes\Test;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Traits\NamespaceHelper;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

#[Service]
final class TestRunnerService
{
    use OutputHelper;
    use NamespaceHelper;

    private array $config;
    private array $results;
    private array $filterGroups = [];
    private array $testClasses = [];

    public function __construct(private Config $configuration)
    {
        $this->config = $this->configuration->get('forge_testing');
        $this->results = [
            'total' => 0,
            'passed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'incomplete' => 0,
            'failures' => [],
            'skipped_tests' => [],
            'incomplete_tests' => []
        ];
    }

    public function runTests(?string $filter = null): array
    {
        foreach ($this->discoverTests() as $testClass) {
            $this->processTestClass($testClass, $filter);
        }

        $this->renderResults();
        return $this->results;
    }

    private function discoverTests(): array
    {
        $testClasses = [];
        $testDirectory = $this->config['test_directory'];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($testDirectory)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                require_once $file->getPathname();
                $className = $this->getClassNameFromFile($file->getPathname());
                if (class_exists($className) && $this->isTestClass($className)) {
                    $testClasses[] = $className;
                }
            }
        }

        return $testClasses;
    }

    private function isTestClass(string $className): bool
    {
        $reflection = new ReflectionClass($className);
        return $reflection->isSubclassOf(TestCase::class);
    }

    private function processTestClass(string $testClass, ?string $filter): void
    {
        $reflection = new ReflectionClass($testClass);

        if ($this->shouldSkipClass($reflection, $filter)) {
            return;
        }

        $this->runTestLifecycle($reflection, [
            'before' => $this->getMethodsWithAttribute($reflection, BeforeEach::class),
            'after' => $this->getMethodsWithAttribute($reflection, AfterEach::class)
        ]);
    }

    private function runTestLifecycle(ReflectionClass $reflection, array $lifecycle): void
    {
        foreach ($reflection->getMethods() as $method) {
            if ($this->isTestMethod($method)) {
                $this->executeTestMethod($reflection, $method, $lifecycle);
            }
        }
    }


    private function executeTestMethod(ReflectionClass $reflection, ReflectionMethod $method, array $lifecycle): void
    {
        $testInstance = $reflection->newInstance();
        $this->results['total']++;

        try {
            if ($skip = $this->getMethodAttribute($method, Skip::class)) {
                $this->handleSkippedTest($method, $skip);
                return;
            }

            if ($incomplete = $this->getMethodAttribute($method, Incomplete::class)) {
                $this->handleIncompleteTest($method, $incomplete);
                return;
            }

            $this->runBeforeEach($testInstance, $lifecycle['before']);
            $this->runTestWithDependencies($testInstance, $method);
            $this->runAfterEach($testInstance, $lifecycle['after']);

            $this->results['passed']++;
        } catch (\Throwable $e) {
            $this->handleTestFailure($method, $e);
        }
    }

    private function handleTestFailure(ReflectionMethod $method, Throwable $e): void
    {
        $this->results['failed']++;
        $this->results['failures'][] = [
            'class' => $method->getDeclaringClass()->getName(),
            'method' => $method->getName(),
            'message' => $e->getMessage(),
            'exception' => $e
        ];
    }

    private function isTestMethod(ReflectionMethod $method): bool
    {
        return (bool) $this->getMethodAttribute($method, Test::class);
    }

    private function getMethodAttribute(ReflectionMethod $method, string $attribute): ?object
    {
        $attributes = $method->getAttributes($attribute);
        return $attributes ? $attributes[0]->newInstance() : null;
    }

    private function getClassAttribute(ReflectionClass $class, string $attribute): ?object
    {
        $attributes = $class->getAttributes($attribute);
        return $attributes ? $attributes[0]->newInstance() : null;
    }

    private function shouldSkipClass(ReflectionClass $class, ?string $filter): bool
    {
        if ($skip = $this->getClassAttribute($class, Skip::class)) {
            $this->handleSkippedClass($class, $skip);
            return true;
        }

        if ($filter && !$this->classMatchesFilter($class, $filter)) {
            return true;
        }

        return false;
    }

    private function classMatchesFilter(ReflectionClass $class, string $filter): bool
    {
        $group = $this->getClassAttribute($class, Group::class);
        return $group && $group->name === $filter;
    }

    private function handlesTestFailure(ReflectionMethod $method, Throwable $e): void
    {
        $this->results['failed']++;
        $this->results['failures'][] = [
            'class' => $method->getDeclaringClass()->getName(),
            'method' => $method->getName(),
            'message' => $e->getMessage(),
            'exception' => $e
        ];
    }

    private function runTestWithDependencies(object $instance, ReflectionMethod $method): void
    {
        if ($depends = $this->getMethodAttribute($method, Depends::class)) {
            $this->runDependency($instance, $depends->testMethod);
        }

        if ($dataProvider = $this->getMethodAttribute($method, DataProvider::class)) {
            $this->runDataProvider($instance, $method, $dataProvider);
        } else {
            $method->invoke($instance);
        }
    }

    private function runDataProvider(object $instance, ReflectionMethod $testMethod, DataProvider $dataProvider): void
    {
        $providerMethod = new ReflectionMethod($instance, $dataProvider->methodName);
        foreach ($providerMethod->invoke($instance) as $dataSet) {
            $testMethod->invokeArgs($instance, $dataSet);
        }
    }

    private function renderResults(): void
    {
        $this->line("Test Results:");
        $this->table(
            ['Total', 'Passed', 'Failed', 'Skipped', 'Incomplete'],
            [[
                $this->results['total'],
                $this->results['passed'],
                $this->results['failed'],
                $this->results['skipped'],
                $this->results['incomplete']
            ]]
        );

        $this->renderFailureDetails();
        $this->renderSkippedTests();
        $this->renderIncompleteTests();
    }

    private function renderFailureDetails(): void
    {
        if (!empty($this->results['failures'])) {
            $this->error("\nFailures:");
            foreach ($this->results['failures'] as $failure) {
                $this->line(sprintf(
                    "%s::%s\n%s\n%s:%d\n",
                    $failure['class'],
                    $failure['method'],
                    $failure['message'],
                    $failure['exception']->getFile(),
                    $failure['exception']->getLine()
                ));
            }
        }
    }

    private function runBeforeEach(object $instance, array $methods): void
    {
        foreach ($methods as $method) {
            $method->invoke($instance);
        }
    }

    private function runAfterEach(object $instance, array $methods): void
    {
        foreach ($methods as $method) {
            $method->invoke($instance);
        }
    }

    private function handleSkippedTest(ReflectionMethod $method, Skip $skip): void
    {
        $this->results['skipped']++;
        $this->results['skipped_tests'][] = [
            'class' => $method->getDeclaringClass()->getName(),
            'method' => $method->getName(),
            'reason' => $skip->reason
        ];
    }

    private function handleIncompleteTest(ReflectionMethod $method, Incomplete $incomplete): void
    {
        $this->results['incomplete']++;
        $this->results['incomplete_tests'][] = [
            'class' => $method->getDeclaringClass()->getName(),
            'method' => $method->getName(),
            'reason' => $incomplete->reason
        ];
    }

    private function handleSkippedClass(ReflectionClass $class, Skip $skip): void
    {
        $this->results['skipped']++;
        $this->results['skipped_tests'][] = [
            'class' => $class->getName(),
            'reason' => $skip->reason
        ];
    }

    private function renderSkippedTests(): void
    {
        if (!empty($this->results['skipped_tests'])) {
            $this->warning("\nSkipped Tests:");
            foreach ($this->results['skipped_tests'] as $skippedTest) {
                $this->line(sprintf(
                    "%s::%s\nReason: %s\n",
                    $skippedTest['class'],
                    $skippedTest['method'] ?? '',
                    $skippedTest['reason']
                ));
            }
        }
    }

    private function renderIncompleteTests(): void
    {
        if (!empty($this->results['incomplete_tests'])) {
            $this->warning("\nIncomplete Tests:");
            foreach ($this->results['incomplete_tests'] as $incompleteTest) {
                $this->line(sprintf(
                    "%s::%s\nReason: %s\n",
                    $incompleteTest['class'],
                    $incompleteTest['method'],
                    $incompleteTest['reason']
                ));
            }
        }
    }

    private function runDependency(object $instance, string $dependencyMethod): void
    {
        $dependency = new ReflectionMethod($instance, $dependencyMethod);
        $dependency->invoke($instance);
    }

    private function getMethodsWithAttribute(ReflectionClass $class, string $attribute): array
    {
        $methods = [];
        foreach ($class->getMethods() as $method) {
            if ($this->getMethodAttribute($method, $attribute)) {
                $methods[] = $method;
            }
        }
        return $methods;
    }
}
