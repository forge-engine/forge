<?php

namespace Forge\Modules\ForgeDebugbar\Collectors;

interface CollectorInterface
{
    public static function collect(...$args): mixed;
}