<?php

namespace Forge\Core\Helpers;

use DateTime;

class DateModifier
{
    private DateTime $dateTime;

    public function __construct(DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    public function subDays(int $days): DateModifier
    {
        $this->dateTime->modify("-{$days} days");
        return $this;
    }

    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    public function __toString(): string
    {
        return $this->dateTime->format('Y-m-d H:i:s');
    }
}