<?php

declare(strict_types=1);

use Forge\Core\Session\SessionInterface;
use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeAuth\Services\RoleService;
use App\Modules\ForgeAuth\Repositories\UserRepository;
use App\Modules\ForgeAuth\Enums\Permission;
use App\Modules\ForgeAuth\Enums\Role;
use Forge\Core\DI\Container;

if (!function_exists("hasRole")) {
    function hasRole(string|array|Role $roleNames): bool
    {
        $user = getCurrentUser();

        if (!$user) {
            return false;
        }

        $roleService = Container::getInstance()->get(RoleService::class);

        if (is_string($roleNames) || $roleNames instanceof Role) {
            $roleName =
                $roleNames instanceof Role ? $roleNames->value : $roleNames;
            return $roleService->userHasRole($user, $roleName);
        }

        foreach ($roleNames as $roleName) {
            $actualRoleName =
                $roleName instanceof Role ? $roleName->value : $roleName;
            if ($roleService->userHasRole($user, $actualRoleName)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists("can")) {
    function can(
        string|array|Permission $permissions,
        mixed $resource = null,
    ): bool {
        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        if ($resource) {
            if (
                method_exists($resource, "getOwnerId") &&
                $resource->getOwnerId() === $user->id
            ) {
                return true;
            }
            if (
                method_exists($resource, "getUserId") &&
                $resource->getUserId() === $user->id
            ) {
                return true;
            }
            if (
                method_exists($resource, "getAuthorId") &&
                $resource->getAuthorId() === $user->id
            ) {
                return true;
            }
        }

        $userPermissions = getAllUserPermissions($user);

        if (is_string($permissions) || $permissions instanceof Permission) {
            $permissionName = $permissions instanceof Permission ? $permissions->value : $permissions;
            return in_array($permissionName, $userPermissions);
        }

        foreach ($permissions as $permission) {
            $permissionName = $permission instanceof Permission ? $permission->value : $permission;
            if (!in_array($permissionName, $userPermissions)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists("getAllUserPermissions")) {
    function getAllUserPermissions(User $user): array
    {
        $roleService = Container::getInstance()->get(RoleService::class);
        $userRoles = $roleService->getUserRoles($user);
        $permissions = [];

        foreach ($userRoles as $role) {
            $rolePermissions = $roleService->getRolePermissions($role);
            foreach ($rolePermissions as $permission) {
                $permissions[] = $permission->name;
            }
        }

        return array_unique($permissions);
    }
}

if (!function_exists("canAny")) {
    function canAny(array $permissions, mixed $resource = null): bool
    {
        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        if ($resource) {
            if (
                method_exists($resource, "getOwnerId") &&
                $resource->getOwnerId() === $user->id
            ) {
                return true;
            }
            if (
                method_exists($resource, "getUserId") &&
                $resource->getUserId() === $user->id
            ) {
                return true;
            }
            if (
                method_exists($resource, "getAuthorId") &&
                $resource->getAuthorId() === $user->id
            ) {
                return true;
            }
        }

        $userPermissions = getAllUserPermissions($user);

        foreach ($permissions as $permission) {
            $permissionName = $permission instanceof Permission ? $permission->value : $permission;
            if (in_array($permissionName, $userPermissions)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists("canAll")) {
    function canAll(array $permissions, mixed $resource = null): bool
    {
        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        if ($resource) {
            if (
                method_exists($resource, "getOwnerId") &&
                $resource->getOwnerId() === $user->id
            ) {
                return true;
            }
            if (
                method_exists($resource, "getUserId") &&
                $resource->getUserId() === $user->id
            ) {
                return true;
            }
            if (
                method_exists($resource, "getAuthorId") &&
                $resource->getAuthorId() === $user->id
            ) {
                return true;
            }
        }

        $userPermissions = getAllUserPermissions($user);

        foreach ($permissions as $permission) {
            $permissionName = $permission instanceof Permission ? $permission->value : $permission;
            if (!in_array($permissionName, $userPermissions)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists("cannot")) {
    function cannot(
        string|array|Permission $permissions,
        mixed $resource = null,
    ): bool {
        return !can($permissions, $resource);
    }
}

if (!function_exists("isOwner")) {
    function isOwner(mixed $resource): bool
    {
        $user = getCurrentUser();
        if (!$user || !$resource) {
            return false;
        }

        if (
            method_exists($resource, "getOwnerId") &&
            $resource->getOwnerId() === $user->id
        ) {
            return true;
        }
        if (
            method_exists($resource, "getUserId") &&
            $resource->getUserId() === $user->id
        ) {
            return true;
        }
        if (
            method_exists($resource, "getAuthorId") &&
            $resource->getAuthorId() === $user->id
        ) {
            return true;
        }

        return false;
    }
}

if (!function_exists("hasRoleEnum")) {
    function hasRoleEnum(Role ...$roles): bool
    {
        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        $roleService = Container::getInstance()->get(RoleService::class);

        foreach ($roles as $role) {
            if (!$roleService->userHasRole($user, $role->value)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists("canEnum")) {
    function canEnum(mixed $resource = null, Permission ...$permissions): bool
    {
        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        $roleService = Container::getInstance()->get(RoleService::class);

        if ($resource) {
            if (
                method_exists($resource, "getOwnerId") &&
                $resource->getOwnerId() === $user->id
            ) {
                return true;
            }
            if (
                method_exists($resource, "getUserId") &&
                $resource->getUserId() === $user->id
            ) {
                return true;
            }
            if (
                method_exists($resource, "getAuthorId") &&
                $resource->getAuthorId() === $user->id
            ) {
                return true;
            }
        }

        foreach ($permissions as $permission) {
            if (!$roleService->userHasPermission($user, $permission->value)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists("getCurrentUser")) {
    function getCurrentUser(): ?User
    {
        try {
            $session = Container::getInstance()->get(SessionInterface::class);
            $userId = $session->get("user_id");

            if (!$userId) {
                return null;
            }

            $userRepository = Container::getInstance()->get(
                UserRepository::class,
            );
            return $userRepository->findById($userId);
        } catch (\Exception $e) {
            return null;
        }
    }
}
