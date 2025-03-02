<?php

namespace Forge\Modules\ForgeEvents;

use Forge\Core\Contracts\Modules\ForgeEventDispatcherInterface;
use Forge\Core\Contracts\Modules\ForgeEventInterface;
use Forge\Modules\ForgeEvents\Services\EventQueue;

class ForgeEventDispatcher implements ForgeEventDispatcherInterface
{
    private array $listeners = [];
    private array $asyncListeners = [];
    private EventQueue $queue;

    public function __construct()
    {
        $this->queue = new EventQueue();
    }

    public function dispatch(string $eventName, $payload = null): void
    {

        $event = $this->createEvent($eventName, $payload);

        foreach ($this->listeners[$eventName] ?? [] as $listener) {
            $this->handleListener($event, $listener);
        }

        foreach ($this->asyncListeners[$eventName] ?? [] as $listener) {
            $this->queue->push($event, $listener);
        }
    }

    public function listen(string $eventName, callable $listener, bool $async = false): void
    {
        $target = $async ? $this->asyncListeners : $this->listeners;
        $target[$eventName][] = $listener;
    }

    private function createEvent(string $name, $payload): ForgeEventInterface
    {
        return new class($name, $payload) implements ForgeEventInterface {
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

    private function handleListener(ForgeEventInterface $event, callable $listener): void
    {
        try {
            call_user_func($listener, $event);
        } catch (\Throwable $e) {
            error_log("Event listener failed: " . $e->getMessage());
        }
    }
}