<?php

namespace App\Modules\ForgeWire\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Service
{
    public function __construct(public ?string $class = null)
    {
    }
}
