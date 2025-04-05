<?php

namespace App\Modules\ForgeNotification\Contracts;

interface ForgeNotificationInterface
{
    public function send(string $channel, array $data): bool;
}
