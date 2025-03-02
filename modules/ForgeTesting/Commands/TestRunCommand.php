<?php

namespace Forge\Modules\ForgeTesting\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgeTesting\TestRunner;

class TestRunCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'test:run';
    }

    public function getDescription(): string
    {
        return 'Run test suite';
    }

    public function execute(array $args): int
    {
        $runner = new TestRunner();
        $start = microtime(true);

        try {
            $results = $runner->runTests($args[0] ?? '');
            $this->outputResults($results, microtime(true) - $start);
            return $results['failed'] === 0 ? 0 : 1;
        } catch (\Throwable $e) {
            $this->error("Test Error: " . $e->getMessage());
            return 1;
        }
    }

    private function outputResults(array $results, float $duration): void
    {
        $this->info("\nTest Results:");
        $this->line("Total: {$results['total']}");
        $this->success("Passed: {$results['passed']}");
        $this->error("Failed: {$results['failed']}");
        $this->line("Duration: " . number_format($duration, 2) . "s");

        if (!empty($results['failures'])) {
            $this->error("\nFailures:");
            foreach ($results['failures'] as $failure) {
                $this->line("- {$failure['class']}::{$failure['method']}");
                $this->line("  {$failure['message']}");
                $this->line("  in {$failure['file']}:{$failure['line']}");
            }
        }
    }
}