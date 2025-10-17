<?php

namespace App\Modules\ForgeWire\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Model
{
    public function __construct(public string $class, public string $idField = "id")
    {
    }
}
