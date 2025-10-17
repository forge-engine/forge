<?php

namespace App\Modules\ForgeWire\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Validate
{
    /**
     * @param string $rules A pipe-separated string of validation rules (e.g., "required|min:5|email").
     * @param string $messages Optional JSON string of custom messages (e.g., '{"required": "The field is needed"}').
     */
    public function __construct(
        public string $rules,
        public string $messages = '[]'
    )
    {
    }
}