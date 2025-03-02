<?php

namespace Forge\Core\Contracts\Modules;

interface ForgeEventSubscriber
{
    public static function subscribe(array $subscriptions): void;
}