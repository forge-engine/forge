<?php

namespace Forge\Core\Helpers;

class Transform
{
    /**
     * Checks if the given data is an object and has a 'toArray' method.
     *
     * @param mixed $data The data to check.
     * @return bool True if data is an object and has toArray method, false otherwise.
     */
    public static function hasToArrayMethod(mixed $data): bool
    {
        return is_object($data) && method_exists($data, 'toArray');
    }
}