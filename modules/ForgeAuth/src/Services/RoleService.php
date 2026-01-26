<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Services;

use App\Modules\ForgeAuth\Models\Role;
use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeAuth\Models\Permission;
use App\Modules\ForgeAuth\Repositories\RoleRepository;
use App\Modules\ForgeAuth\Repositories\UserRepository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Contracts\Database\QueryBuilderInterface;

#[Service]
final class RoleService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly UserRepository $userRepository,
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    public function createRole(string $name, ?string $description = null): Role
    {
        $existingRole = $this->roleRepository->findByName($name);
        if ($existingRole) {
            throw new \InvalidArgumentException(
                "Role with name '{$name}' already exists",
            );
        }

        return $this->roleRepository->createRole($name, $description);
    }

    public function deleteRole(Role $role): void
    {
        $this->queryBuilder
            ->table("user_roles")
            ->where("role_id", "=", $role->id)
            ->delete();

        $this->queryBuilder
            ->table("role_permissions")
            ->where("role_id", "=", $role->id)
            ->delete();

        $this->roleRepository->deleteRole($role);
    }

    public function addPermissionToRole(
        Role $role,
        Permission $permission,
    ): void {
        $exists = $this->queryBuilder
            ->table("role_permissions")
            ->where("role_id", "=", $role->id)
            ->where("permission_id", "=", $permission->id)
            ->first();

        if (!$exists) {
            $this->queryBuilder->table("role_permissions")->insert([
                "role_id" => $role->id,
                "permission_id" => $permission->id,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
        }
    }

    public function removePermissionFromRole(
        Role $role,
        Permission $permission,
    ): void {
        $this->queryBuilder
            ->table("role_permissions")
            ->where("role_id", "=", $role->id)
            ->where("permission_id", "=", $permission->id)
            ->delete();
    }

    public function assignRoleToUser(Role $role, User $user): void
    {
        $exists = $this->queryBuilder
            ->table("user_roles")
            ->where("user_id", "=", $user->id)
            ->where("role_id", "=", $role->id)
            ->first();

        if (!$exists) {
            $this->queryBuilder->table("user_roles")->insert([
                "user_id" => $user->id,
                "role_id" => $role->id,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
        }
    }

    public function removeRoleFromUser(Role $role, User $user): void
    {
        $this->queryBuilder
            ->table("user_roles")
            ->where("user_id", "=", $user->id)
            ->where("role_id", "=", $role->id)
            ->delete();
    }

    public function getUserRoles(User $user): array
    {
        $userRoleRows = $this->queryBuilder
            ->table("user_roles")
            ->where("user_id", "=", $user->id)
            ->get();

        if (empty($userRoleRows)) {
            return [];
        }

        $roleIds = array_column($userRoleRows, "role_id");
        return Role::query()->whereIn("id", $roleIds)->get();
    }

    public function getRolePermissions(Role $role): array
    {
        $rolePermissionRows = $this->queryBuilder
            ->table("role_permissions")
            ->where("role_id", "=", $role->id)
            ->get();

        if (empty($rolePermissionRows)) {
            return [];
        }

        $permissionIds = array_column($rolePermissionRows, "permission_id");
        return Permission::query()->whereIn("id", $permissionIds)->get();
    }

    public function getAllRoles(): array
    {
        return $this->roleRepository->getAllRoles();
    }

    public function findRoleById(int $id): ?Role
    {
        return $this->roleRepository->findById($id);
    }

    public function findRoleByName(string $name): ?Role
    {
        return $this->roleRepository->findByName($name);
    }

    public function userHasRole(User $user, string $roleName): bool
    {
        $roles = $this->getUserRoles($user);
        foreach ($roles as $role) {
            if ($role->name === $roleName) {
                return true;
            }
        }
        return false;
    }

    public function userHasPermission(User $user, string $permission): bool
    {
        $roles = $this->getUserRoles($user);
        foreach ($roles as $role) {
            $permissions = $this->getRolePermissions($role);
            foreach ($permissions as $perm) {
                if ($perm->name === $permission) {
                    return true;
                }
            }
        }
        return false;
    }
}
