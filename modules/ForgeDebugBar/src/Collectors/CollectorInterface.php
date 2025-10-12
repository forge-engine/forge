<?php

namespace App\Modules\ForgeDebugbar\Collectors;

interface CollectorInterface
{
    public static function collect(...$args): mixed;
}
