<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Commands;

use App\Modules\ForgeEvents\Services\EventDispatcher;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name:'queue:work', description:'Process queued events')]
final class QueueWorkCommand extends Command
{
    use OutputHelper;

    private static bool $shutdown = false;

    public function __construct(private readonly EventDispatcher $dispatcher)
    {
    }

    public function execute(array $args): int
    {
        $workers = 1;
        foreach ($args as $a) {
            if (str_starts_with($a, '--workers=')) {
                $workers = (int) explode('=', $a, 2)[1];
            } elseif (str_starts_with($a, '-w=')) {
                $workers = (int) explode('=', $a, 2)[1];
            }
        }
        $workers = max(1, $workers);

        $queues = env('QUEUE_LIST', ['default']);

        pcntl_async_signals(true);
        pcntl_signal(SIGINT, fn () => self::$shutdown = true);
        pcntl_signal(SIGTERM, fn () => self::$shutdown = true);

        foreach ($queues as $queue) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                $this->error("Unable to fork worker for queue {$queue}");
                return 1;
            }
            if ($pid === 0) {
                $this->spawnWorkers($queue, $workers);
                exit(0);
            }
        }

        while (pcntl_wait($status) !== -1) {
        }
        $this->info('All workers terminated.');
        return 0;
    }

    private function workerLoop(string $queue): void
    {
        $pid = getmypid();
        $this->info("Worker for queue '{$queue}' started (PID {$pid})");

        $jobsHandled = 0;
        $backOff     = 0.1;
        $maxBackOff  = 5.0;
        $gcCycle     = 50;

        $currentJobId = null;

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->dispatcher;
        pcntl_signal(SIGTERM, function () use (&$currentJobId, $queue, $dispatcher) {
            if ($currentJobId) {
                $dispatcher->release($currentJobId, 0);
            }
            exit(0);
        });


        while (!self::$shutdown) {
            pcntl_signal_dispatch();
            $currentJobId = null;
            $jobProcessed = false;

            while ($id = $this->dispatcher->processNextEvent($queue)) {
                $currentJobId = $id;
                $this->info("Queue {$queue} processed job {$id}");
                $jobsHandled++;
                $backOff     = 0.1;
                $jobProcessed = true;

                if ($jobsHandled % $gcCycle === 0) {
                    gc_collect_cycles();
                }
            }

            if (!$jobProcessed) {
                $next = $this->dispatcher->getNextJobDelay($queue) ?? 0;
                $sleep = $next > 0 ? min($next, $maxBackOff) : $backOff;
                usleep((int)($sleep * 1_000_000));
                $backOff = min($backOff * 2, $maxBackOff);
            }
        }

        $this->warning("Worker for queue '{$queue}' (PID {$pid}) exiting gracefully.");
    }

    private function spawnWorkers(string $queue, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                $this->error("Unable to fork worker {$i}/{$count} for queue {$queue}");
                continue;
            }
            if ($pid === 0) {
                $this->workerLoop($queue);
                exit(0);
            }
        }

        while (pcntl_wait($status) !== -1) {
        }
    }
}
