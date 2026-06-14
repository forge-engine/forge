<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use App\Modules\ForgeAuth\Models\User;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Contracts\Database\QueryBuilderInterface;

#[Service]
final class PermissionService
{
  public function __construct(
    private readonly QueryBuilderInterface $queryBuilder
  ) {
  }

  /**
   * Get all permissions for a user based on their roles.
   *
   * @return array<string> Array of permission strings
   */
  public function getUserPermissions(User $user): array
  {
    $rows = $this->queryBuilder
        ->table('permissions')
        ->select('permissions.name')
        ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
        ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
        ->where('user_roles.user_id', '=', $user->id)
        ->get();

    return array_values(array_unique(array_column($rows, 'name')));
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
      return true;
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
      return true;
    }

    $userPermissions = $this->getUserPermissions($user);

    foreach ($permissions as $permission) {
      if (!in_array($permission, $userPermissions, true)) {
        return false;
      }
    }

    return true;
  }
}
