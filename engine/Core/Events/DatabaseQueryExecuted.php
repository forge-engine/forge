<?php

namespace Forge\Core\Events;

use Forge\Core\Contracts\Events\EventInterface;

class DatabaseQueryExecuted implements EventInterface
{
    public function __construct(
        public readonly string    $query,
        public readonly array     $bindings,
        public readonly float|int $timeInMilliseconds,
        public readonly string    $connectionName,
        public readonly string    $origin
    )
    {
    }
}