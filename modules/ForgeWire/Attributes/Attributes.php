<?php

namespace App\Modules\ForgeWire\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class State
{
}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Model
{
    public function __construct(public string $class, public string $idField = "id")
    {
    }
}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class DTO
{
    public function __construct(public string $class)
    {
    }
} // requires toArray()/fromArray()

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Service
{
    public function __construct(public ?string $class = null)
    {
    }
}

#[\Attribute(\Attribute::TARGET_METHOD)]
class Action
{
}

#[\Attribute(\Attribute::TARGET_METHOD)]
class Computed
{
}
