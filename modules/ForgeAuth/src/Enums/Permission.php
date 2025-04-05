<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Enums;

enum Permission : string
{
    case UsersRead = 'users.read';
    case UsersWrite = 'user.write';
    case UsersExport = 'users.export';
    case UsersDelete = 'users.delete';
}
