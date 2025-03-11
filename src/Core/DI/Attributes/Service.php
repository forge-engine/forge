<?php
declare(strict_types=1);

namespace Forge\Core\DI\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Service
{
    public function __construct(
        public ?string $id = null,
        public bool    $singleton = true
    )
    {
    }
}