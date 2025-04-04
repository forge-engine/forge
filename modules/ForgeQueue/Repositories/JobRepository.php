<?php

namespace Forge\Modules\ForgeQueue\Repositories;

use Forge\Core\Helpers\Date;
use Forge\Modules\ForgeExplicitOrm\Repository\BaseRepository;
use Forge\Modules\ForgeOrm\Pagination\Paginator;
use Forge\Modules\ForgeQueue\DTO\JobDTO;
use Forge\Modules\ForgeQueue\Enums\JobStatus;

class JobRepository extends BaseRepository
{
    protected string $dtoClass = JobDTO::class;
    protected string $table = "queue_jobs";

    public function getNextAvailableJob(string $queue, int $locktime): ?JobDTO
    {
        $this->database->beginTransaction();

        error_log("getNextAvailableJob - Queue: " . $queue);
        error_log("getNextAvailableJob - Status: " . JobStatus::PENDING->value);
        error_log("getNextAvailableJob - Current Time: " . date('Y-m-d H:i:s'));

        try {
            $job = $this->where([
                ['queue', '=', $queue],
                ['status', '=', JobStatus::PENDING->value],
                ['scheduled_at', '<=', date('Y-m-d H:i:s')],

            ])->orderBy('created_at')->first();

            if ($job) {
                error_log("getNextAvailableJob - Job Found: ID: " . $job->id . ", Queue: " . $job->queue . ", Status: " . $job->status->value . ", Scheduled: " . ($job->scheduled_at ? $job->scheduled_at->format('Y-m-d H:i:s') : 'NULL') . ", Locked: " . ($job->locked_until ? $job->locked_until->format('Y-m-d H:i:s') : 'NULL'));
                $this->update($job->id, [
                    'locked_until' => date('Y-m-d H:i:s', time() + $locktime)
                ]);
            } else {
                error_log("getNextAvailableJob - No Job Found");
            }

            $this->database->commit();
            return $job;
        } catch (\Throwable $e) {
            $this->database->rollback();
            throw $e;
        }
    }

    public function paginate(int $page, int $perPage, string $status, string $queue): Paginator
    {
        $query = $this->where([
            'status' => $status,
            'queue' => $queue
        ]);

        $total = $query->count();
        $results = $query->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        return new Paginator($results, $total, $perPage, $page);
    }

    public function count(array $conditions = []): int
    {
        $where = [];
        $params = [];

        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                $where[] = "{$key} {$value[0]} :{$key}";
                $params[":{$key}"] = $value[1];
            } else {
                $where[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        return (int)$this->database->query($sql, $params)[0]['COUNT(*)'];
    }

    public function limit(int $limit): self
    {
        $this->currentQuery .= " LIMIT {$limit}";
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->currentQuery .= " OFFSET {$offset}";
        return $this;
    }

    /**
     * Get all unique queue names from the database
     *
     * @return array List of queue names
     */
    public function getQueues(): array
    {
        $sql = "SELECT DISTINCT queue FROM {$this->table} ORDER BY queue ASC";
        $results = $this->database->query($sql);
        
        return array_map(fn($row) => $row['queue'], $results);
    }

    /**
     * Retry a failed job
     *
     * @param string|int $id The job ID
     * @return bool Whether the job was successfully queued for retry
     */
    public function retryJob(string|int $id): bool
    {
        $job = $this->find($id);
        
        if (!$job) {
            return false;
        }
        
        return (bool) $this->update($id, [
            'status' => JobStatus::PENDING->value,
            'scheduled_at' => date('Y-m-d H:i:s'),
            'locked_until' => null,
            'error' => null
        ]);
    }

    /**
     * Retry all failed jobs
     *
     * @return int Number of jobs queued for retry
     */
    public function retryAllFailed(): int
    {
        $sql = "UPDATE {$this->table} SET 
            status = :status, 
            scheduled_at = :scheduled_at, 
            locked_until = NULL, 
            error = NULL 
            WHERE status = :failed_status";
            
        $params = [
            ':status' => JobStatus::PENDING->value,
            ':scheduled_at' => date('Y-m-d H:i:s'),
            ':failed_status' => JobStatus::FAILED->value
        ];
        
        return $this->database->execute($sql, $params);
    }
}
