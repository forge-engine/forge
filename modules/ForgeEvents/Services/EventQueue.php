<?php

namespace Forge\Modules\ForgeEvents\Services;

use Forge\Core\Contracts\Modules\ForgeEventInterface;
use Forge\Core\Helpers\App;

class EventQueue
{
    private string $driver;
    private int $maxRetries;

    public function __construct()
    {
        $config = App::config()->get("forge_events");
        $this->driver = $config['queue_driver'];
        $this->maxRetries = $config['max_retries'];
    }

    public function push(ForgeEventInterface $event, callable $listener): void
    {
        match ($this->driver) {
            'database' => $this->storeInDatabase($event, $listener),
            'redis' => $this->storeInRedis($event, $listener),
            default => $this->handleSync($event, $listener)
        };
    }

    private function storeInDatabase(ForgeEventInterface $event, callable $listener): void
    {
        // TODO: Implement database queue storage
    }

    private function storeInRedis(ForgeEventInterface $event, callable $listener): void
    {
        // TODO: Implement database queue storage
    }

    private function handleSync(ForgeEventInterface $event, callable $listener): void
    {
        try {
            call_user_func($listener, $event);
        } catch (\Throwable $e) {
            error_log("Async listener failed: " . $e->getMessage());
        }
    }

    public function process(): void
    {
        $events = match ($this->driver) {
            'database' => $this->fetchFromDatabase(),
            'redis' => $this->fetchFromRedis(),
            default => []
        };

        foreach ($events as $eventData) {
            $event = $this->reconstructEvent($eventData);
            $listener = $eventData['listener'];
            $retries = $eventData['retries'] ?? 0;

            if ($retries < $this->maxRetries) {
                try {
                    call_user_func($listener, $event);
                    $this->removeFromQueue($eventData);
                } catch (\Throwable $e) {
                    error_log("Queued event processing failed: " . $e->getMessage());
                    $this->incrementRetryCount($eventData);
                }
            } else {
                error_log("Max retries reached for event: " . $event->getName());
                $this->handleMaxRetries($eventData);
            }
        }
    }

    private function incrementRetryCount(array $eventData): void
    {
        $retries = $eventData['retries'] ?? 0;
        $retries++;

        match ($this->driver) {
            'database' => $this->updateRetryCountInDatabase($eventData, $retries),
            'redis' => $this->updateRetryCountInRedis($eventData, $retries),
            default => throw new \RuntimeException("Retry count increment not implemented for driver: {$this->driver}")
        };
    }

    private function updateRetryCountInDatabase(array $eventData, int $retries): void
    {
        // TODO: Implement updating the retry count in the database
        // $this->db->table('event_queue')->where('id', $eventData['id'])->update(['retries' => $retries]);
    }

    private function updateRetryCountInRedis(array $eventData, int $retries): void
    {
        // TODO: Implement updating the retry count in Redis
        // $this->redis->hset($eventData['key'], 'retries', $retries);
    }

    private function fetchFromDatabase(): array
    {
        // TODO: Implement fetching events from the database
        return [];
    }

    private function fetchFromRedis(): array
    {
        // TODO: Implement fetching events from Redis
        return [];
    }

    private function handleMaxRetries(array $eventData): void
    {
        // Log the failure
        error_log("Max retries reached for event: " . $eventData['name']);

        // Optionally, alert an administrator or take other actions
        // Example: Send an email to the admin, log to a monitoring system, etc.
        $this->removeFromQueue($eventData);
    }

    private function reconstructEvent(array $eventData): ForgeEventInterface
    {
        return new class($eventData['name'], $eventData['payload']) implements ForgeEventInterface {
            public function __construct(
                private string $name,
                private mixed  $payload
            )
            {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getPayload(): mixed
            {
                return $this->payload;
            }
        };
    }

    private function removeFromQueue(array $eventData): void
    {
        match ($this->driver) {
            'database' => $this->removeFromDatabase($eventData),
            'redis' => $this->removeFromRedis($eventData),
            default => throw new \RuntimeException("Event removal not implemented for driver: {$this->driver}")
        };
    }

    private function removeFromDatabase(array $eventData): void
    {
        // TODO: Implement removing the event from the database
        // $this->db->table('event_queue')->where('id', $eventData['id'])->delete();
    }

    private function removeFromRedis(array $eventData): void
    {
        // TODO: Implement removing the event from Redis
        // $this->redis->del($eventData['key']);
    }
}