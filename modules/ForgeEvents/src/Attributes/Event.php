<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Attributes;

use App\Modules\ForgeEvents\Enums\QueuePriority;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Event
{
    public function __construct(
        public string $queue = 'default',
        public int $maxRetries = 1,
        public int $retryDelay = 1000,
        public int $processAfterMinutes = 10,
        public QueuePriority $priority = QueuePriority::NORMAL
    ) {
    }
}
