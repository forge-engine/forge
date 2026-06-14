<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeAuth\Repositories\UserRepository;
use Forge\Core\Config\Config;
use Forge\Core\Session\SessionInterface;

#[Service]
#[Requires(SessionInterface::class, version: '>=0.1.0')]
#[Requires(Config::class, version: '>=0.1.0')]
final class UserContext
{
    private ?User $cachedUser = null;

    public function __construct(
        private readonly Config $config,
        private readonly SessionInterface $session,
        private readonly UserRepository $users,
    ) {
    }

    /**
     * Get current logged in user.
     *
     * @return User|null User object or null if not logged in
     */
    public function current(): ?User
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        $userId = $this->session->get('user_id');

        if (!$userId) {
            return null;
        }

        return $this->cachedUser = $this->users->findById((int) $userId);
    }

    /**
     * Is current user authenticated.
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->current() !== null;
    }

    /**
     * Set the current user manually.
     */
    public function setCurrentUser(User $user): void
    {
        $this->cachedUser = $user;
    }
}
