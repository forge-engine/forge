<?php

namespace Forge\Modules\ForgeQueue;

use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;
use Forge\Modules\ForgeQueue\DTO\JobDTO;
use Forge\Modules\ForgeQueue\Enums\JobStatus;
use Forge\Modules\ForgeQueue\Repositories\JobRepository;
use Forge\Modules\ForgeQueue\Contracts\JobInterface;

class Queue
{
    private DatabaseInterface $db;
    private JobRepository $repository;
    private array $config;

    public function __construct(DatabaseInterface $db, array $config = [])
    {
        $this->db = $db;
        $this->repository = new JobRepository($db);
        $this->config = array_merge([
            'max_attempts' => 3,
            'retry_delay' => 60,
            'lock_time' => 300,
            'queue' => 'default'
        ], $config);
    }

    public function dispatch(JobInterface $job): void
    {
        $this->repository->create([
            'queue' => $this->config['queue'],
            'payload' => $this->serializeJob($job),
            'attempts' => 0,
            'scheduled_at' => $job->delay > 0 ?
                date('Y-m-d H:i:s', time() + $job->delay) :
                null,
            'status' => JobStatus::PENDING->value
        ]);
    }

    public function process(int $maxJobs = 10): void
    {
        $jobsProcessed = 0;

        while ($jobsProcessed < $maxJobs && $job = $this->getNextJob()) {
            try {
                $this->markAsProcessing($job);
                $jobInstance = $this->unserializeJob($job->payload);
                $jobInstance->handle();
                $this->markAsComplete($job);
            } catch (\Throwable $e) {
                $this->handleFailure($job, $e);
            }
            $jobsProcessed++;
        }
    }

    private function getNextJob(): ?JobDTO
    {
        return $this->repository->getNextAvailableJob(
            $this->config['queue'],
            $this->config['lock_time']
        );
    }

    private function serializeJob(JobInterface $job): string
    {
        return json_encode([
            'job_class' => get_class($job),
            'data' => $job->serialize()
        ], JSON_THROW_ON_ERROR);
    }

    private function unserializeJob(string $payload): JobInterface
    {
        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $class = $data['job_class'];
        return new $class(...$data['data']);
    }

    private function markAsProcessing(JobDTO $job): void
    {
        $this->repository->update($job->id, [
            'status' => JobStatus::PROCESSING->value,
            'locked_until' => date('Y-m-d H:i:s', time() + $this->config['lock_time']),
            'attempts' => $job->attempts + 1
        ]);
    }

    private function markAsComplete(JobDTO $job): void
    {
        $this->repository->update($job->id, [
            'status' => JobStatus::COMPLETED->value,
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function handleFailure(JobDTO $job, \Throwable $e): void
    {
        if ($job->attempts >= $this->config['max_attempts']) {
            $this->markAsFailed($job, $e);
        } else {
            $this->reschedule($job);
        }
    }

    private function reschedule(JobDTO $job): void
    {
        $this->repository->update($job->id, [
            'status' => JobStatus::PENDING->value,
            'scheduled_at' => date('Y-m-d H:i:s', time() + $this->getRetryDelay($job)),
            'locked_until' => null
        ]);
    }

    private function getRetryDelay(JobDTO $job): int
    {
        return $this->config['retry_delay'] * pow(2, $job->attempts);
    }

    private function markAsFailed(JobDTO $job, \Throwable $e): void
    {
        $this->repository->update($job->id, [
            'status' => JobStatus::FAILED->value,
            'error' => $e->getMessage(),
            'failed_at' => date('Y-m-d H:i:s')
        ]);
    }
}