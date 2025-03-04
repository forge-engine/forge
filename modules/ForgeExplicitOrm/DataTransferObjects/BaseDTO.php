<?php

namespace Forge\Modules\ForgeExplicitOrm\DataTransferObjects;

abstract class BaseDTO
{
    /**
     * Convert DTO to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Convert DTO to JSON String
     *
     * @param int $options JSON encoding options (example JSON_PRETTY_PRINT)
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

}