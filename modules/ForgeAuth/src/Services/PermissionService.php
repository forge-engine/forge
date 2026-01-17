<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use App\Modules\ForgeAuth\Models\User;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Session\SessionInterface;

#[Service]
final class PermissionService
{
  private const string SESSION_PERMISSIONS_KEY = 'user_permissions';

  public function __construct(
    private readonly SessionInterface $session
  ) {
  }

  /**
   * Get all permissions for a user.
   * For now, permissions are stored in user metadata.
   * Later this can be expanded to use a full RBAC system with roles.
   *
   * @return array<string> Array of permission strings
   */
  public function getUserPermissions(User $user): array
  {
    // Check session first (cached)
    if ($this->session->has(self::SESSION_PERMISSIONS_KEY)) {
      return $this->session->get(self::SESSION_PERMISSIONS_KEY, []);
    }

    // Get permissions from user metadata
    $permissions = [];
    if ($user->metadata !== null) {
      $metadata = $user->metadata;
      // Check if metadata has permissions array
      if (is_array($metadata) && isset($metadata['permissions']) && is_array($metadata['permissions'])) {
        $permissions = $metadata['permissions'];
      } elseif (is_object($metadata) && property_exists($metadata, 'permissions')) {
        $permissions = is_array($metadata->permissions) ? $metadata->permissions : [];
      }
    }

    // Store in session for future requests
    $this->session->set(self::SESSION_PERMISSIONS_KEY, $permissions);

    return $permissions;
  }

  /**
   * Check if a user has a specific permission.
   */
  public function hasPermission(User $user, string $permission): bool
  {
    $permissions = $this->getUserPermissions($user);
    return in_array($permission, $permissions, true);
  }

  /**
   * Check if a user has any of the specified permissions.
   */
  public function hasAnyPermission(User $user, array $permissions): bool
  {
    if (empty($permissions)) {
      return true; // No permissions required means access granted
    }

    $userPermissions = $this->getUserPermissions($user);

    foreach ($permissions as $permission) {
      if (in_array($permission, $userPermissions, true)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Check if a user has all of the specified permissions.
   */
  public function hasAllPermissions(User $user, array $permissions): bool
  {
    if (empty($permissions)) {
      return true; // No permissions required means access granted
    }

    $userPermissions = $this->getUserPermissions($user);

    foreach ($permissions as $permission) {
      if (!in_array($permission, $userPermissions, true)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Get permissions from session (for current user).
   * Returns empty array if no user is logged in.
   */
  public function getCurrentUserPermissions(): array
  {
    if (!$this->session->has(self::SESSION_PERMISSIONS_KEY)) {
      return [];
    }

    return $this->session->get(self::SESSION_PERMISSIONS_KEY, []);
  }

  /**
   * Check if current user has a specific permission.
   */
  public function currentUserHasPermission(string $permission): bool
  {
    $permissions = $this->getCurrentUserPermissions();
    return in_array($permission, $permissions, true);
  }

  /**
   * Check if current user has any of the specified permissions.
   */
  public function currentUserHasAnyPermission(array $permissions): bool
  {
    if (empty($permissions)) {
      return true;
    }

    $userPermissions = $this->getCurrentUserPermissions();

    foreach ($permissions as $permission) {
      if (in_array($permission, $userPermissions, true)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Clear cached permissions from session.
   */
  public function clearCache(): void
  {
    $this->session->remove(self::SESSION_PERMISSIONS_KEY);
  }
}
