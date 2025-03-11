<?php
declare(strict_types=1);

namespace Forge\Core\Http\Attributes;

use Attribute;

#[
    Attribute(
        Attribute::TARGET_CLASS |
            Attribute::TARGET_METHOD |
            Attribute::IS_REPEATABLE
    )
]
class Middleware
{
    public function __construct(public string $middlewareClass) {}
}
