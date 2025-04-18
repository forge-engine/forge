<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Commands;

use App\Modules\ForgeEvents\Services\EventDispatcher;
use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'queue:work', description: 'Process queued events')]
class QueueWorkCommand extends Command
{
    private static bool $shutdown = false;

    public function __construct(private EventDispatcher $eventDispatcher)
    {
    }
    public function execute(array $args): int
    {
        $workers = 1;
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--workers=')) {
                $workers = (int) substr($arg, strlen('--workers='));
                break;
            }
        }

        $this->info("Starting {$workers} queue worker(s)...");

        pcntl_async_signals(true);
        pcntl_signal(SIGINT, function () {
            self::$shutdown = true;
            $this->warning("Received SIGINT. Shutting down...");
        });
        pcntl_signal(SIGTERM, function () {
            self::$shutdown = true;
            $this->warning("Received SIGTERM. Shutting down...");
        });

        for ($i = 0; $i < $workers; $i++) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->error("Failed to fork process");
                return 1;
            }

            if ($pid === 0) {
                $this->info("Worker #{$i} started with PID " . getmypid());

                while (!self::$shutdown) {
                    $this->eventDispatcher->processNextEvent();
                    usleep(900_000);
                }

                $this->warning("Worker #{$i} exiting gracefully...");
                exit(0);
            }
        }

        foreach (range(1, $workers) as $_) {
            pcntl_wait($status);
        }

        $this->info("All workers shut down.");

        return 0;
    }
}
