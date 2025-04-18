<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Queues;

use App\Modules\ForgeEvents\Contracts\QueueInterface;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Attributes\Service;
use PDO;

#[Service(singleton: true)]
class DatabaseQueue implements QueueInterface
{
    private string $queueName = 'default';

    public function __construct(private QueryBuilder $queryBuilder)
    {
    }

    public function setQueue(string $queueName): self
    {
        $this->queueName = $queueName;
        return $this;
    }

    public function push(string $payload, int $priority = 100, int $delay = 0, int $retries = 1): void
    {
        $processAt = null;

        if ($delay > 0) {
            $delayInSeconds = (int) ($delay / 1000);
            $processAtTimestamp = time() + $delayInSeconds;
            $processAt = date('Y-m-d H:i:s', $processAtTimestamp);
        }

        $this->queryBuilder->setTable('queue_jobs')->insert([
            'queue' => $this->queueName,
            'payload' => $payload,
            'priority' => $priority,
            'max_retries' => $retries,
            'failed_at' => null,
            'process_at' => $processAt ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'reserved_at' => null
        ]);
    }

    public function pop(): ?array
    {
        $now = date('Y-m-d H:i:s');

        if ($this->isSqlite()) {
            // $this->queryBuilder->getConnection()->getPdo()->exec('BEGIN IMMEDIATE TRANSACTION');
        } else {
        }
        $this->queryBuilder->beginTransaction();
        try {
            $job = $this->queryBuilder->setTable('queue_jobs')
                ->where('queue', '=', $this->queueName)
                ->whereRaw('(process_at IS NULL OR process_at <= :now)', ['now' => $now])
                ->whereNull('reserved_at')
                ->orderBy('priority', 'ASC')
                ->orderBy('created_at', 'ASC')
                ->limit(1);

            if (!$this->isSqlite()) {
                $job->lockForUpdate();
            }

            $job = $job->first();

            if ($job) {
                $this->markJobAsReserved($job['id']);
                $this->queryBuilder->commit();
                return ['id' => $job['id'], 'payload' => $job['payload']];
            }

            $this->queryBuilder->rollback();
            return null;
        } catch (\Throwable $e) {
            $this->queryBuilder->rollback();
            throw $e;
        }
    }

    public function release(int $jobId, int $delay = 0): void
    {
        $current = $this->queryBuilder->reset()->setTable('queue_jobs')
            ->where('id', '=', $jobId)
            ->first();

        $attempts = ($current['attempts'] ?? 0) + 1;

        $processAt = null;
        if ($delay > 0) {
            $delayInSeconds = (int) ($delay / 100);
            $processAtTimestamp = time() + $delayInSeconds;
            $processAt = date('Y-m-d H:i:s', $processAtTimestamp);
        }

        $this->queryBuilder->reset()->setTable('queue_jobs')
        ->where('id', '=', $jobId)
        ->update([
            'reserved_at' => null,
            'process_at' => $processAt,
            'attempts' => $attempts,
        ]);
    }


    public function delete(int $jobId): void
    {
        $this->queryBuilder->setTable('queue_jobs')->where('id', '=', $jobId)->delete();
    }

    public function count(): int
    {
        return $this->queryBuilder->setTable('queue_jobs')->where('queue', '=', $this->queueName)->count();
    }

    public function clear(): void
    {
        $this->queryBuilder->setTable('queue_jobs')->where('queue', '=', $this->queueName)->delete();
    }

    protected function markJobAsReserved(int $jobId): void
    {
        $this->queryBuilder->setTable('queue_jobs')
            ->where('id', '=', $jobId)
            ->update(['reserved_at' => date('Y-m-d H:i:s')]);
    }

    private function isSqlite(): bool
    {
        return $this->queryBuilder->getConnection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }
}
