<?php

namespace App\Modules\ForgeWire\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Validate
{
    /**
     * @param string $rules A pipe-separated string of validation rules (e.g., "required|min:5|email").
     * @param array|string $messages Optional custom messages. Can be an array (preferred) or JSON string (backward compatible).
     *                                Array example: ['required' => 'Name is required', 'min' => 'Name must be at least :value characters']
     *                                JSON example: '{"required": "Name is required", "min": "Name must be at least :value characters"}'
     */
    public function __construct(
        public string $rules,
        public array|string $messages = []
    ) {
    }
}