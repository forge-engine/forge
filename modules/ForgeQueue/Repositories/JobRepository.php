<?php

namespace Forge\Modules\ForgeQueue\Repositories;

use Forge\Modules\ForgeExplicitOrm\Repository\BaseRepository;
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
                ['locked_until', 'IS NULL']
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
}
