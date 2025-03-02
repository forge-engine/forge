<?php

namespace Forge\Core\Contracts\Modules;

interface ForgeEventDispatcherInterface
{

    public function dispatch(string $eventName, $payload = null): void;

    public function listen(string $eventName, callable $listener, bool $async = false): void;

}