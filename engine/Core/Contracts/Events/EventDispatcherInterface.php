<?php

namespace Forge\Core\Contracts\Events;

interface EventDispatcherInterface
{
    /**
     * Dispatch an event to all registered listeners.
     *
     * @param EventInterface $event
     * @return void
     */
    public function dispatch(EventInterface $event): void;

    /**
     * Register a listener for a specific event class.
     *
     * @param string $eventClassName The fully qualified class name of the event to listen for.
     * @param ListenerInterface|string $listener Listener instance or class name of the listener.
     *                                           If a string, it will be resolved from the Container.
     * @return void
     */
    public function listen(string $eventClassName, $listener): void;
}