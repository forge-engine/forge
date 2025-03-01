<?php

namespace Forge\Core\Events;

use Forge\Core\Contracts\Events\EventDispatcherInterface;
use Forge\Core\Contracts\Events\EventInterface;
use Forge\Core\Contracts\Events\ListenerInterface;
use Forge\Core\DependencyInjection\Container;

class EventDispatcher implements EventDispatcherInterface
{

    /**
     * @var array<string, array<ListenerInterface|string>> Event class name => array of listeners (instances or class names)
     */
    private array $listeners = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function dispatch(EventInterface $event): void
    {
        $eventClassName = get_class($event);

        if (isset($this->listeners[$eventClassName])) {
            foreach ($this->listeners[$eventClassName] as $listener) {
                if ($listener instanceof ListenerInterface) {
                    $listener->handle($event);
                } elseif (is_string($listener) && class_exists($listener)) {
                    $listenerInstance = $this->container->get($listener);
                    if ($listenerInstance instanceof ListenerInterface) {
                        $listenerInstance->handle($event);
                    }
                }
            }
        }
    }


    public function listen(string $eventClassName, $listener): void
    {
        if (!is_subclass_of($eventClassName, EventInterface::class)) {
            throw new \InvalidArgumentException("Event class name must implement EventInterface: {$eventClassName}");
        }

        if (!($listener instanceof ListenerInterface) && !(is_string($listener) && class_exists($listener) && is_subclass_of($listener, ListenerInterface::class))) {
            throw new \InvalidArgumentException("Listener must be an instance of ListenerInterface or a class name implementing ListenerInterface: " . (is_object($listener) ? get_class($listener) : gettype($listener)));
        }

        $this->listeners[$eventClassName][] = $listener;
    }
}