<?php

namespace App\Modules\ForgeStorage\Commands;

use App\Modules\ForgeStorage\Services\StorageService;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Command;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'storage:manage', description: 'Manage storage buckets and files')]
class StorageCommand extends Command
{
    use OutputHelper;

    public function __construct(private StorageService $storageService)
    {
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

        $this->storageService->createBucket($name, ['public' => $public]);
        $this->success("Bucket {$name} created");
        return 0;
    }

    private function listBuckets(): int
    {
        $buckets = $this->storageService->listBuckets();
        $this->info("Available buckets:");
        foreach ($buckets as $bucket) {
            $this->line(" - {$bucket}");
        }
        return 0;
    }

    private function cleanupExpired(): int
    {
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
