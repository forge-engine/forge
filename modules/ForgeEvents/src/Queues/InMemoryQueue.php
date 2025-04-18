<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Queues;

use App\Modules\ForgeEvents\Contracts\Queueinterface;
use App\Modules\ForgeEvents\Enums\QueuePriority;
use SplPriorityQueue;

final class InMemoryQueue implements Queueinterface
{
    private SplPriorityQueue $queue;
    private int $insertionCount = 0;

    public function __construct()
    {
        $this->queue = new SplPriorityQueue();
        $this->queue->setExtractFlags(SplPriorityQueue::EXTR_DATA);
    }

    public function push(string $payload, int $priority = QueuePriority::NORMAL->value): void
    {
        $this->queue->insert($payload, [
            $priority,
            -$this->insertionCount++
        ]);
    }

    public function pop(): ?array
    {
        if ($this->queue->isEmpty()) {
            return null;
        }

        return $this->queue->extract();
    }

    public function count(): int
    {
        return $this->queue->count();
    }

    public function clear(): void
    {
        $this->queue = new SplPriorityQueue();
        $this->insertionCount = 0;
    }

    public function release(int $jobId, int $delay = 0): void
    {
    }
}
