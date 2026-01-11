<?php

namespace App\Modules\ForgeWire\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class State
{
    public function __construct(public bool $shared = false)
    {
    }
}