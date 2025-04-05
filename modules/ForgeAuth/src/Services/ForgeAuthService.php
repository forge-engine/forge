<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeAuth\Contracts\ForgeAuthInterface;
use App\Modules\ForgeAuth\Dto\UserDto;
use App\Modules\ForgeAuth\Exceptions\LoginException;
use App\Modules\ForgeAuth\Exceptions\UserRegistrationException;
use App\Modules\ForgeAuth\Repositories\UserRepository;
use Exception;
use Forge\Core\Config\Config;
use Forge\Core\Helpers\Flash;
use Forge\Core\Session\SessionInterface;

#[Service]
#[Provides(interface: ForgeAuthInterface::class, version: '0.1.2')]
#[Requires(SessionInterface::class)]
#[Requires(Config::class)]
#[Requires(UserRepository::class)]
final class ForgeAuthService implements ForgeAuthInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private Config $config,
        private SessionInterface $session
    ) {
    }

    public function register(array $credentials): bool
    {
        try {
            $payload = [
                "username" => $credentials["username"],
                 "password" => password_hash($credentials["password"], PASSWORD_BCRYPT),
                 "email" => $credentials["email"],
            ];

            $this->userRepository->create($payload);
            return true;
        } catch (Exception $e) {
            throw new UserRegistrationException();
        }
    }

    public function login(array $credentials): UserDto
    {
        $this->validateLoginAttempt();

        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user || !password_verify($credentials['password'], $user->password)) {
            $this->handleFailedLogin();
            Flash::set("error", "Invalid credentials");
            throw new LoginException();
        }

        $this->session->regenerate();
        $this->session->set('user_id', $user->id);
        $this->session->set('user_email', $user->email);
        $this->resetLoginAttempts();

        return $user;
    }

    public function logout(): void
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $this->session->clear();
    }

    public function user(): ?UserDto
    {
        $userId = $this->session->get('user_id');
        return $userId ? $this->userRepository->findById($userId) : null;
    }

    private function validateLoginAttempt(): void
    {
        $attempts = (int) $this->session->get('login_attempts', 0);
        $lastAttempt = (int) $this->session->get('last_login_attempt', 0);

        $maxAttempts = (int) $this->config->get('security.password.max_login_attempts', 5);
        $lockoutTime = (int) $this->config->get('security.password.lockout_time', 300);

        if ($attempts >= $maxAttempts && time() - $lastAttempt < $lockoutTime) {
            Flash::set("error", "Too many login attempts. Please try again later");
            throw new LoginException();
        }
    }

    private function handleFailedLogin(): void
    {
        $attempts = (int) $this->session->get('login_attempts', 0) + 1;
        $this->session->set('login_attempts', $attempts);
        $this->session->set('last_login_attempt', time());
    }

    private function resetLoginAttempts(): void
    {
        $this->session->remove('login_attempts');
        $this->session->remove('last_login_attempt');
    }
}
