<?php
declare(strict_types=1);

namespace App\Modules\ForgeAuth\Traits;

use App\Modules\ForgeAuth\Models\User;

trait HasCurrentUser
{
    private function getCurrentUser(): ?User
    {
        try {
            $session = \Forge\Core\DI\Container::getInstance()->get(
                \Forge\Core\Session\SessionInterface::class,
            );
            $userId = $session->get("user_id");

            if (!$userId) {
                return null;
            }

            $userRepository = \Forge\Core\DI\Container::getInstance()->get(
                \App\Modules\ForgeAuth\Repositories\UserRepository::class,
            );
            return $userRepository->findById($userId);
        } catch (\Exception $e) {
            return null;
        }
    }
}
