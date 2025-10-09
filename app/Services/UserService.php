<?php
declare(strict_types=1);

namespace App\Services;

use App\Modules\ForgeAuth\Models\User;
use Forge\Core\Cache\Attributes\Cache;
use Forge\Core\DI\Attributes\Service;
use Forge\Traits\CacheLifecycleHooks;

#[Service]
class UserService
{
    use CacheLifecycleHooks;

    #[Cache(key: 'user->{id}', ttl: 600)]
    public function findUser(int $id): ?User
    {
        return User::find($id);
    }

    public static function onCacheSave($instance, $args, $key, $data): void
    {
        echo "Custom cache save logic for user {$data->id}\n";
    }

}