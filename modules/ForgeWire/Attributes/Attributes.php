<?php

namespace App\Modules\ForgeWire\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class State
{
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Model
{
    public function __construct(public string $class, public string $idField = "id")
    {
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class DTO
{
    public function __construct(public string $class)
    {
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Service
{
    public function __construct(public ?string $class = null)
    {
    }
}

#[Attribute(Attribute::TARGET_METHOD)]
class Action
{
}

#[Attribute(Attribute::TARGET_METHOD)]
class Computed
{
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Validate
{
    /**
     * @param string $rules A pipe-separated string of validation rules (e.g., "required|min:5|email").
     * @param string $messages Optional JSON string of custom messages (e.g., '{"required": "The field is needed"}').
     */
    public function __construct(
        public readonly string $rules,
        public readonly string $messages = '[]'
    )
    {
    }
}