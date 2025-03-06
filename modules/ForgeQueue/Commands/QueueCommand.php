<?php

namespace Forge\Modules\ForgeQueue\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Helpers\App;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgeQueue\Queue;

class QueueCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'queue:process';
    }

    public function getDescription(): string
    {
        return 'Start a worker to process queued events';
    }

    public function execute(array $args): int
    {
        $queue = new Queue(App::db());
        try {
            error_log("PHP Timezone: " . date_default_timezone_get());
            error_log("Current PHP Time (PHP Timezone): " . date('Y-m-d H:i:s'));
            while (true) {
                $this->info('Processing queue jobs..');
                $queue->process(100);
                $this->success("Queue processing finished");
                sleep(5);
            }
        } catch (\Throwable $e) {
            $this->error("Error during queue processing:");
            $this->info($e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1; // Indicate an error
        }

        return 0;
    }
}
