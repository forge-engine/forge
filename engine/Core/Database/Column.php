<?php

declare(strict_types=1);

namespace Forge\Core\Database;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Column
{
    public function __construct(
        public string $type,
        public bool   $primary = false,
        public bool   $nullable = false,
        public bool   $unique = false
    )
    {
    }
}