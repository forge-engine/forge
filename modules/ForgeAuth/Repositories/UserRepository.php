<?php

namespace Forge\Modules\ForgeAuth\Repositories;

use Forge\Modules\ForgeAuth\DTO\UserDTO;
use Forge\Modules\ForgeExplicitOrm\Repository\BaseRepository;

class UserRepository extends BaseRepository
{
    protected string $dtoClass = UserDTO::class;
    protected string $table = 'users';

    public function findByEmail(string $email): ?UserDTO
    {
        $user = $this->whereCriteria(['email' => $email]);
        return $user[0] ?? null;
    }

    public function updatePassword(int $userId, string $newPassword): UserDTO
    {
        return $this->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_BCRYPT),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}