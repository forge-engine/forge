<?php

namespace App\Modules\ForgeWire\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Action
{
    public function __construct(
        public bool $submit = false,
    ) {
    }
}
