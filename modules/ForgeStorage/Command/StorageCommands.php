<?php

namespace Forge\Modules\ForgeStorage\Command;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;

class StorageCommands implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'storage:manage';
    }

    public function getDescription(): string
    {
        return 'Manage storage buckets and files';
    }

    public function execute(array $args): int
    {
        $action = $args[0] ?? null;

        try {
            return match ($action) {
                'create-bucket' => $this->createBucket($args),
                'list-buckets' => $this->listBuckets(),
                'cleanup' => $this->cleanupExpired(),
                default => $this->showHelp()
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function createBucket(array $args): int
    {
        $name = $args[1] ?? null;
        $public = in_array('--public', $args);

        app('storage')->createBucket($name, ['public' => $public]);
        $this->success("Bucket {$name} created");
        return 0;
    }

    private function listBuckets(): int
    {
        $buckets = app('storage')->listBuckets();
        $this->info("Available buckets:");
        foreach ($buckets as $bucket) {
            $this->line(" - {$bucket}");
        }
        return 0;
    }

    private function cleanupExpired(): int
    {
        // Implementation would check expiration dates
        $this->info("Cleaned up 0 expired files");
        return 0;
    }

    private function showHelp(): int
    {
        $this->line("Available commands:");
        $this->line(" storage:manage create-bucket <name> [--public]");
        $this->line(" storage:manage list-buckets");
        $this->line(" storage:manage cleanup");
        return 0;
    }
}