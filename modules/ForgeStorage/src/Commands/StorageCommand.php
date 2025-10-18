<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Commands;

use App\Modules\ForgeStorage\Services\StorageService;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\CLI\Traits\Wizard;

#[Cli(
    command: 'storage:bucket',
    description: 'Manage storage buckets and files',
    usage: 'storage:bucket <action> [--name=BUCKET_NAME] [--public]',
    examples: [
        'storage:bucket create --name=my-bucket --public',
        'storage:bucket list',
        'storage:bucket cleanup'
    ]
)]
final class StorageCommand extends Command
{
    use OutputHelper;
    use Wizard;

    #[Arg(
        name: 'action',
        description: 'Action to perform (create, list, cleanup)',
        required: true
    )]
    private string $action;

    #[Arg(
        name: 'name',
        description: 'Bucket name (required for create)',
        required: false
    )]
    private ?string $bucketName = null;

    #[Arg(
        name: 'public',
        description: 'Mark bucket as public (only for create)',
        default: false,
        required: false
    )]
    private bool $public = false;

    public function __construct(private readonly StorageService $storageService)
    {
    }

    public function execute(array $args): int
    {
        $this->wizard($args);

        try {
            return match ($this->action) {
                'create' => $this->createBucket(),
                'list' => $this->listBuckets(),
                'cleanup' => $this->cleanupExpired(),
                default => $this->showHelp()
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function createBucket(): int
    {
        if (!$this->bucketName) {
            $this->error("Bucket name is required for create action.");
            return 1;
        }

        $this->storageService->createBucket($this->bucketName, ['public' => $this->public]);
        $this->success("Bucket {$this->bucketName} created" . ($this->public ? " (public)" : ""));
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
        $count = 0;
        //$count = $this->storageService->cleanupExpiredFiles();
        $this->info("Cleaned up {$count} expired file(s)");
        return 0;
    }

    private function showHelp(): int
    {
        $this->line("Available commands:");
        $this->line(" storage:bucket create --name=BUCKET_NAME [--public]");
        $this->line(" storage:bucket list");
        $this->line(" storage:bucket cleanup");
        return 0;
    }
}