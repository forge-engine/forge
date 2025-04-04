<?php

namespace Forge\Modules\ForgeQueue\DTO;

use Forge\Modules\ForgeExplicitOrm\DataTransferObjects\BaseDTO;
use Forge\Modules\ForgeQueue\Enums\JobStatus;
use DateTime;

class JobDTO extends BaseDTO
{
    public int $id;
    public string $queue;
    public string $payload;
    public int $attempts = 0;
    public JobStatus $status;
    public ?string $error = null;
    public ?DateTime $scheduled_at = null;
    public ?DateTime $locked_until = null;
    public DateTime $created_at;
    public ?DateTime $completed_at = null;
    public ?DateTime $failed_at = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = (int)$data["id"];
        $this->queue = (string)$data["queue"];
        $this->payload = (string)$data["payload"];
        $this->attempts = (int)$data["attempts"];
        $this->status = JobStatus::from($data["status"]);
        $this->error = $data["error"] ? (string)$data["error"] : null;

        $this->scheduled_at = $data["scheduled_at"]
            ? new DateTime($data["scheduled_at"])
            : null;

        $this->locked_until = $data["locked_until"]
            ? new DateTime($data["locked_until"])
            : null;

        $this->created_at = new DateTime($data["created_at"]);

        $this->completed_at = $data["completed_at"]
            ? new DateTime($data["completed_at"])
            : null;

        $this->failed_at = $data["failed_at"]
            ? new DateTime($data["failed_at"])
            : null;
    }

    /**
     * Serialize dates for JSON output
     */
    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id,
            "queue" => $this->queue,
            "payload" => $this->payload,
            "attempts" => $this->attempts,
            "status" => $this->status->value,
            "error" => $this->error,
            "scheduled_at" => $this->scheduled_at?->format(DateTime::ATOM),
            "locked_until" => $this->locked_until?->format(DateTime::ATOM),
            "created_at" => $this->created_at->format(DateTime::ATOM),
            "completed_at" => $this->completed_at?->format(DateTime::ATOM),
            "failed_at" => $this->failed_at?->format(DateTime::ATOM),
        ];
    }

    /**
     * Get the job execution duration in seconds
     */
    public function duration(): ?int
    {
        if (!$this->completed_at && !$this->failed_at) {
            return null;
        }

        $end = $this->completed_at ?? $this->failed_at;
        return $end->getTimestamp() - $this->created_at->getTimestamp();
    }

    /**
     * Check if job is currently locked
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until > new DateTime();
    }

    /**
     * Get the next available retry timestamp
     */
    public function nextRetryAt(): ?DateTime
    {
        if ($this->status !== JobStatus::PENDING) {
            return null;
        }

        return $this->scheduled_at ?? $this->created_at;
    }
}
