<?php

namespace App\Modules\ForgeWire\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class DTO
{
    public function __construct(public string $class)
    {
    }
} // requires toArray()/fromArray()
