<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use App\Modules\ForgeAuth\Requeriments\PasswordRequeriments;
use App\Modules\ForgeWire\Exceptions\ValidationException;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Redirect;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeAuth\Contracts\ForgeAuthInterface;
use App\Modules\ForgeAuth\Dto\CreateUserData;
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
  public function __construct(
    private readonly Config $config,
    private readonly SessionInterface $session,
    private readonly UserRepository $users,
    private readonly UserContext $userContext,
    private readonly ?PermissionService $permissionService = null
  ) {
  }

  /**
   * @param array<string, mixed> $credentials
   * @return bool
   * @throws UserRegistrationException
   */
  public function register(array $credentials): bool
  {
    try {

      PasswordRequeriments::validate($credentials['password']);

      $data = new CreateUserData(
        identifier: $credentials["identifier"],
        email: $credentials["email"],
        password: password_hash($credentials["password"], PASSWORD_BCRYPT),
        status: 'active',
        metadata: $credentials["metadata"] ?? null
      );

      $this->users->create($data);
      return true;

    } catch (ValidationException $e) {
      Flash::set("error", $e->getMessage());
      Redirect::to('auth/register');
    } catch (Exception $e) {
      Flash::set("error", $e->getMessage());
      Redirect::to('auth/register');
    }
  }

  /**
   * @throws LoginException
   */
  public function login(array $credentials): User
  {
    $this->validateLoginAttempt();

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

    if ($this->permissionService !== null) {
      $permissions = $this->permissionService->getUserPermissions($user);
      $this->session->set('user_permissions', $permissions);
    }

    return $user;
  }

  /**
   * @throws LoginException
   */
  private function validateLoginAttempt(): void
  {
    $attempts = (int) $this->session->get('login_attempts', 0);
    $lastAttempt = (int) $this->session->get('last_login_attempt', 0);

    $maxAttempts = (int) $this->config->get('forge_auth.password.max_login_attempts', 5);
    $lockoutTime = (int) $this->config->get('forge_auth.password.lockout_time', 300);

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

  public function logout(): void
  {
    if (!$this->session->isStarted()) {
      $this->session->start();
    }

    $this->session->clear();
  }

  /**
   * Get permissions for the current user.
   *
   * @return array<string> Array of permission strings
   */
  public function getUserPermissions(): array
  {
    if ($this->permissionService === null) {
      return [];
    }

    $user = $this->userContext->current();
    if ($user === null) {
      return [];
    }

    return $this->permissionService->getUserPermissions($user);
  }
}
