<?php

namespace Forge\Core\Contracts\Events;

interface ListenerInterface
{
    /**
     * Handle the dispatched event.
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function handle(EventInterface $event): void;
}