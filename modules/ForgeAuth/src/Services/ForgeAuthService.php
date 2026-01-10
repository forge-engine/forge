<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeAuth\Contracts\ForgeAuthInterface;
use App\Modules\ForgeAuth\Dto\CreateUserData;
use App\Modules\ForgeAuth\Exceptions\JwtTokenExpiredException;
use App\Modules\ForgeAuth\Exceptions\JwtTokenInvalidException;
use App\Modules\ForgeAuth\Exceptions\LoginException;
use App\Modules\ForgeAuth\Exceptions\UserRegistrationException;
use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeAuth\Repositories\UserRepository;
use Exception;
use Forge\Core\Config\Config;
use Forge\Core\Helpers\Flash;
use Forge\Core\Session\SessionInterface;

#[Service]
#[Provides(interface: ForgeAuthInterface::class, version: '0.1.7')]
#[Requires(SessionInterface::class, version: '>=0.1.0')]
#[Requires(Config::class, version: '>=0.1.0')]
final class ForgeAuthService implements ForgeAuthInterface
{
    private static array $customClaimsCallbacks = [];
    private ?User $cachedUser = null;
    private ?bool $jwtEnabled = null;
    private ?int $jwtTtl = null;
    private ?int $jwtRefreshTtl = null;

    public function __construct(
        private readonly Config $config,
        private readonly SessionInterface $session,
        private readonly JwtService $jwtService,
        private readonly UserRepository $users
    ) {
    }

    public static function addCustomClaimsCallback(callable $callback): void
    {
        self::$customClaimsCallbacks[] = $callback;
    }

    /**
     * @throws UserRegistrationException
     */
    public function register(array $credentials): bool
    {
        try {

            $this->assertPasswordLength($credentials['password']);

            $data = new CreateUserData(
                identifier: $credentials["identifier"],
                email: $credentials["email"],
                password: password_hash($credentials["password"], PASSWORD_BCRYPT),
                status: 'active',
                metadata: $credentials["metadata"] ?? null
            );

            $this->users->create($data);
            return true;
        } catch (Exception $e) {
            throw new UserRegistrationException();
        }
    }

    /**
     * @throws LoginException
     */
    public function login(array $credentials): User
    {
        $this->validateLoginAttempt();

        $this->assertPasswordLength($credentials['password']);

        $user = $this->users->findByIdentifier($credentials['identifier']);

        if (!$user || !password_verify($credentials['password'], $user->password)) {
            $this->handleFailedLogin();
            Flash::set("error", "Invalid credentials");
            throw new LoginException();
        }

        $this->session->regenerate();
        $this->session->set('user_id', $user->id);
        $this->session->set('user_identifier', $user->identifier);
        $this->session->set('user_email', $user->email);
        $this->resetLoginAttempts();

        return $user;
    }

    /**
     * @throws LoginException
     */
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

    private function assertPasswordLength(string $password): void
    {
        $max = (int) $this->config->get('security.password.max_password_length', 128);
        $min = (int) $this->config->get('security.password.min_password_length', 6);

        if (strlen($password) > $max) {
            throw new LoginException();
        }

        if (strlen($password) < $min) {
            throw new LoginException();
        }
    }

    private function resetLoginAttempts(): void
    {
        $this->session->remove('login_attempts');
        $this->session->remove('last_login_attempt');
    }

    public function logout(): void
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $this->session->clear();
    }

    public function user(): ?User
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        $userId = $this->session->get('user_id');
        $user = $userId ? $this->users->findById((int) $userId) : null;
        $this->cachedUser = $user;

        return $user;
    }

    public function setUser(User $user): void
    {
        $this->cachedUser = $user;
    }

    public function issueToken(User $user): array
    {
        if (!$this->isJwtEnabled()) {
            throw new \RuntimeException('JWT is not enabled');
        }

        $now = time();
        $ttl = $this->getJwtTtl();
        $refreshTtl = $this->getJwtRefreshTtl();

        $accessPayload = [
            'user_id' => $user->id,
            'exp' => $now + $ttl,
            'iat' => $now,
            'jti' => bin2hex(random_bytes(16)),
            'type' => 'access',
        ];

        $refreshPayload = [
            'user_id' => $user->id,
            'exp' => $now + $refreshTtl,
            'type' => 'refresh',
            'jti' => bin2hex(random_bytes(16)),
        ];

        $accessPayload = $this->applyCustomClaims($user, $accessPayload);
        $refreshPayload = $this->applyCustomClaims($user, $refreshPayload);

        $accessToken = $this->jwtService->encode($accessPayload);
        $refreshToken = $this->jwtService->encode($refreshPayload);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $ttl,
        ];
    }

    public function refreshToken(string $refreshToken): ?array
    {
        try {
            $payload = $this->jwtService->decode($refreshToken);
        } catch (JwtTokenInvalidException | JwtTokenExpiredException) {
            return null;
        }

        if (($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        $userId = $payload['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $user = $this->users->findById((int) $userId);
        if (!$user) {
            return null;
        }

        return $this->issueToken($user);
    }

    public function resolveUserFromToken(string $token): ?User
    {
        try {
            $payload = $this->jwtService->decode($token);
        } catch (JwtTokenInvalidException | JwtTokenExpiredException $e) {
            throw $e;
        }

        $userId = $payload['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $user = $this->users->findById((int) $userId);
        if ($user) {
            $this->cachedUser = $user;
        }

        return $user;
    }

    private function applyCustomClaims(User $user, array $basePayload): array
    {
        if (empty(self::$customClaimsCallbacks)) {
            return $basePayload;
        }

        $protected = ['user_id', 'exp', 'iat', 'jti', 'type'];

        foreach (self::$customClaimsCallbacks as $callback) {
            $customClaims = $callback($user, $basePayload);
            if (is_array($customClaims) && !empty($customClaims)) {
                foreach ($customClaims as $key => $value) {
                    if (!in_array($key, $protected, true)) {
                        $basePayload[$key] = $value;
                    }
                }
            }
        }

        return $basePayload;
    }

    private function isJwtEnabled(): bool
    {
        if ($this->jwtEnabled === null) {
            $this->jwtEnabled = (bool) $this->config->get('security.jwt.enabled', false);
        }

        return $this->jwtEnabled;
    }

    private function getJwtTtl(): int
    {
        if ($this->jwtTtl === null) {
            $this->jwtTtl = (int) $this->config->get('security.jwt.ttl', 900);
        }

        return $this->jwtTtl;
    }

    private function getJwtRefreshTtl(): int
    {
        if ($this->jwtRefreshTtl === null) {
            $this->jwtRefreshTtl = (int) $this->config->get('security.jwt.refresh_ttl', 604800);
        }

        return $this->jwtRefreshTtl;
    }
}
