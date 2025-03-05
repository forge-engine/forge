<?php

namespace Forge\Modules\ForgeAuth;

use Forge\Http\Exceptions\ValidationException;
use Forge\Http\Validator;
use Forge\Modules\ForgeAuth\Repositories\UserRepository;
use Forge\Modules\ForgeAuth\DTO\UserDTO;
use Forge\Http\Session;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;

class AuthService
{
    private UserRepository $userRepository;
    private Session $session;
    private array $config;

    public function __construct(
        DatabaseInterface $database,
        Session           $session,
        array             $config = []
    )
    {
        $this->userRepository = new UserRepository($database);
        $this->session = $session;
        $this->config = array_merge([
            'password_cost' => 12,
            'max_login_attempts' => 5,
            'lockout_tyime' => 300 // 5 minutes
        ], $config);
    }

    public function register(array $credentials): UserDTO
    {
        $this->validateRegistration($credentials);

        $user = $this->userRepository->create([
            'email' => $credentials['email'],
            'password' => password_hash($credentials['password'], PASSWORD_BCRYPT, [
                'cost' => $this->config['password_cost']
            ]),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->session->regenerate();
        $this->session->set('user_id', $user->id);

        return $user;
    }

    public function login(array $credentials): UserDTO
    {
        $this->validateLoginAttemp();

        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user || !password_verify($credentials['password'], $user->password)) {
            $this->handleFailedLogin();
            throw new \RuntimeException('Invalid credentials');
        }

        $this->session->regenerate();
        $this->session->set('user_id', $user->id);
        $this->session->set('user_email', $user->email);
        $this->resetLoginAttempts();

        return $user;
    }

    public function logout(): void
    {
        $this->session->destroy();
    }

    public function user(): ?UserDTO
    {
        $userId = $this->session->get('user_id');
        return $userId ? $this->userRepository->find($userId) : null;
    }

    private function validateRegistration(array $credentials): void
    {
        $validator = new Validator($credentials, [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required|min:8',
        ]);

        if (!$validator->validate()) {
            throw new ValidationException($validator->getErrors());
        }
    }

    private function validateLoginAttemp(): void
    {
        $attempts = $this->session->get('login_attempts', 0);
        $lastAttempt = $this->session->get('last_login_attempt');

        if ($attempts >= $this->config['max_login_attempts'] && time() - $lastAttempt < $this->config['lockout_tyime']) {
            throw new \RuntimeException('Too many login attempts. Please try agin later.');
        }
    }

    private function handleFailedLogin(): void
    {
        $attempts = $this->session->get('login_attempts', 0) + 1;
        $this->session->set('login_attempts', $attempts);
        $this->session->set('last_login_attempt', time());
    }

    private function resetLoginAttempts(): void
    {
        $this->session->remove('login_attempts');
        $this->session->remove('last_login_attempt');
    }

}