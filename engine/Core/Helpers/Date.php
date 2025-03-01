<?php

namespace Forge\Core\Helpers;

use DateTime;

class Date
{
    /**
     * Returns a DateTime object representing the current date and time.
     *
     * @return DateModifier
     */
    public static function now(): DateModifier
    {
        return new DateModifier(new DateTime());
    }
}