<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Services;

use App\Modules\ForgeEvents\Attributes\Event;
use App\Modules\ForgeEvents\Attributes\EventListener;
use App\Modules\ForgeEvents\Contracts\Queueinterface;
use App\Modules\ForgeEvents\Enums\QueuePriority;
use App\Modules\ForgeEvents\Exceptions\EventException;
use App\Modules\ForgeEvents\Queues\DatabaseQueue;
use App\Modules\ForgeEvents\Queues\FileQueue;
use App\Modules\ForgeEvents\Queues\InMemoryQueue;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Config\Environment;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Traits\TimeTrait;
use ReflectionClass;
use RuntimeException;
use Throwable;

#[Service(singleton: true)]
final class EventDispatcher
{
    use OutputHelper;
    use TimeTrait;

    private array $listeners = [];
    private Queueinterface $queue;
    private Container $container;
    private QueryBuilder $queryBuilder;

    public function __construct()
    {
        $this->queryBuilder = Container::getInstance()->get(QueryBuilder::class);
        $this->container = Container::getInstance();
        $this->queue = $this->driverSetup();
    }

    private function driverSetup(): Queueinterface
    {
        $driver = Environment::getInstance()->get('QUEUE_DRIVER', 'file');
        $adapter = match ($driver) {
            'file' => new FileQueue("forge_events"),
            'in-memory' => new InMemoryQueue(),
            'database' => new DatabaseQueue($this->queryBuilder),
            default => throw new RuntimeException('Unsupported driver')
        };

        return $adapter;
    }

    public function addListener(string $eventClass, callable $handler): void
    {
        $this->listeners[$eventClass][] = $handler;
    }

    /**
     * @throws EventException
     */
    #[EventListener(Event::class)]
    public function dispatch(object $event): void
    {
        $eventReflection = new ReflectionClass($event);
        $eventAttribute = $eventReflection->getAttributes(Event::class)[0] ?? null;

        if (!$eventAttribute) {
            throw new EventException("Event missing #[Event] attribute");
        }

        $eventMetadata = $eventAttribute->newInstance();

        $delayMilliseconds = $this->toMilliseconds($eventMetadata->delay) ?? 0;

        $this->queue->push(serialize([
            'event' => $event,
            'class' => $eventReflection->getName(),
            'metadata' => $eventMetadata,
            'attempts' => 0
        ]), $eventMetadata->priority->value, $delayMilliseconds, $eventMetadata->maxRetries, $eventMetadata->queue);
    }

    public function getNextJobDelay(string $queue = 'default'): ?float
    {
        return $this->queue->getNextJobDelay($queue);
    }

    public function processNextEvent(string $queue = 'default'): string
    {
        $job = $this->queue->pop($queue);
        if (!$job) {
            return '';
        }

        $payload = unserialize($job['payload']);

        $this->handleEvent($payload, $job['id'] ?? null);
        return (string) $job['id'];
    }

    private function handleEvent(array $payload, ?int $jobId): void
    {
        $now = date('Y-m-d H:i:s');
        $eventClass = $payload['class'];
        $payload['jobId'] = $jobId;
        $this->comment("Handling event: {$eventClass}");

        if (!isset($this->listeners[$eventClass])) {
            $this->warning("No listeners for event: {$eventClass}");
            if ($jobId !== null) {
                $this->deleteJob($jobId);
            }
            return;
        }

        $this->info("Processing event: {$eventClass} at: {$now}");

        foreach ($this->listeners[$eventClass] as $handler) {
            try {
                call_user_func($handler, $payload['event']);
                if ($jobId !== null) {
                    $this->deleteJob($jobId);
                }
            } catch (Throwable $e) {
                $this->handleFailure($payload, $e, $jobId);
            }
        }
    }

    private function handleFailure(array $payload, Throwable $e, ?int $jobId): void
    {
        $this->error("Metadata: " . print_r($payload['metadata'], true));
        $retries = $payload['metadata']->maxRetries ?? 3;
        $attempts = $payload['attempts'] ?? 0;

        $this->error("Event {$payload['class']} failed. Attempt: " . ($attempts + 1));

        if ($attempts < $retries) {
            $this->retryEvent($payload, $attempts);
            if ($jobId !== null) {
                $this->deleteJob($jobId);
            }
        } else {
            if ($jobId !== null) {
                $this->markJobAsFailed($jobId);
                $this->deleteJob($jobId);
            }
        }
    }

    private function retryEvent(array $payload, int $attempts): void
    {
        $payload['attempts'] = $attempts + 1;

        $retryDelaySeconds = ($payload['metadata']->retryDelay ?? 0) / 100;
        $retryProcessAfter = microtime(true) + $retryDelaySeconds;

        $this->queue->push(serialize([
            'event' => $payload['event'],
            'class' => $payload['class'],
            'metadata' => $payload['metadata'],
            'processAfter' => $retryProcessAfter,
            'attempts' => $payload['attempts'],
            $payload['metadata']->queue
        ]), QueuePriority::LOW->value, (int)($retryDelaySeconds * 1000));

        $this->warning("Retrying event {$payload['class']} (attempt {$payload['attempts']})");
    }

    private function markJobAsFailed(?int $jobId): void
    {
        if ($jobId !== null) {
            $this->queryBuilder->reset()->setTable('queue_jobs')
                ->where('id', '=', $jobId)
                ->update([
                    'failed_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    private function release(int $jobId, ?int $delay = 0): void
    {
        $this->queue->release($jobId, $delay);
    }

    private function deleteJob(?int $jobId): void
    {
        if ($jobId !== null) {
            $this->queryBuilder->reset()->setTable('queue_jobs')->where('id', '=', $jobId)->delete();
        }
    }
}
