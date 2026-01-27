<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Enums;

enum Permission: string
{
    case USER_READ = "user.read";
    case USER_WRITE = "user.write";
}
