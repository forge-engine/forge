<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Queues;

use App\Modules\ForgeEvents\Contracts\QueueInterface;
use App\Modules\ForgeEvents\Enums\QueuePriority;
use Forge\Traits\FileHelper;

final class FileQueue implements QueueInterface
{
    use FileHelper;

    private string $queuePath;

    public function __construct(string $queueName)
    {
        $this->queuePath = BASE_PATH . "/storage/queues/{$queueName}";
        $this->ensureDirectoryExists($this->queuePath);
    }

    public function push(string $payload, int $priority = QueuePriority::NORMAL->value, int $delay = 0): void
    {
        $priorityValue = $priority;
        $processAfter = 0;
        if ($delay > 0) {
            $processAfter = microtime(true) + ($delay / 1000);
        }

        $jobData = [
            'payload' => $payload,
            'processAfter' => $processAfter,
            'attempts' => 0
        ];

        // Serialize the combined job data
        $jobContent = serialize($jobData);

        // Filename format remains the same, but file content changes
        $filename = sprintf('%d_%s_%s.job', $priorityValue, uniqid('job'), time());
        file_put_contents("{$this->queuePath}/{$filename}", $jobContent);
    }

    public function pop(): ?array
    {
        $files = glob("{$this->queuePath}/*.job");

        if (empty($files)) {
            return null;
        }

        usort($files, function ($a, $b) {
            $priorityA = (int)substr(basename($a), 0, strpos(basename($a), '_'));
            $priorityB = (int)substr(basename($b), 0, strpos(basename($b), '_'));

            if ($priorityA === $priorityB) {
                $timeA = (int)substr(basename($a), strrpos(basename($a), '_') + 1, -4);
                $timeB = (int)substr(basename($b), strrpos(basename($b), '_') + 1, -4);
                return $timeA <=> $timeB;
            }

            return $priorityA <=> $priorityB;
        });

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $handle = @fopen($file, 'r+');
            if (!$handle) {
                continue;
            }

            if (flock($handle, LOCK_EX | LOCK_NB)) {
                $content = stream_get_contents($handle);

                $jobData = @unserialize($content);

                if ($jobData === false || (isset($jobData['processAfter']) && $jobData['processAfter'] > microtime(true))) {
                    flock($handle, LOCK_UN);
                    fclose($handle);
                    if ($jobData === false) {
                        error_log("Failed to unserialize job file: " . $file);
                        @unlink($file);
                    }
                    continue;
                }

                $originalPayload = $jobData['payload'] ?? null;
                $attempts = $jobData['attempts'] ?? 0;
                $jobId = null;

                if ($originalPayload === null) {
                    error_log("Job file {$file} has no payload.");
                    flock($handle, LOCK_UN);
                    fclose($handle);
                    @unlink($file);
                    continue;
                }

                @unlink($file);

                flock($handle, LOCK_UN);
                fclose($handle);

                $processedPayload = unserialize($originalPayload);
                $processedPayload['attempts'] = $attempts;
                $processedPayload['jobId'] = $jobId;


                return ['id' => $jobId, 'payload' => serialize($processedPayload)];
            } else {
                fclose($handle);
                continue;
            }
        }

        return null;
    }

    public function count(): int
    {
        return count(glob("{$this->queuePath}/*.job"));
    }

    public function clear(): void
    {
        $files = glob("{$this->queuePath}/*.job");
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function release(int $jobId, int $delay = 0): void
    {
    }
}
