<?php

declare(strict_types=1);

namespace App\Events;

use App\Modules\ForgeEvents\Attributes\Event;
use App\Modules\ForgeEvents\Enums\QueuePriority;

#[Event(queue: 'page_visits', maxRetries: 5, priority: QueuePriority::HIGH, processAfterMinutes: 1)]
final class TestPageVisitedEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $visitedAt
    ) {
    }
}
