<?php

declare(strict_types=1);

use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeAuth\Services\RoleService;
use App\Modules\ForgeAuth\Enums\Permission;
use App\Modules\ForgeAuth\Enums\Role;
use Forge\Core\DI\Container;

if (!function_exists('hasRole')) {
    function hasRole(string|array $roleNames): bool
    {
        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        $roleService = Container::getInstance()->get(RoleService::class);
        
        if (is_string($roleNames)) {
            return $roleService->userHasRole($user, $roleNames);
        }

        foreach ($roleNames as $roleName) {
            if ($roleService->userHasRole($user, $roleName)) {
                return true;
            }
        }
        
        return false;
    }
}

if (!function_exists('can')) {
    function can(string|array $permissions, mixed $resource = null): bool
    {
        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        $roleService = Container::getInstance()->get(RoleService::class);
        
        // Enhanced resource-level permission check with multiple ownership methods
        if ($resource) {
            if (method_exists($resource, 'getOwnerId') && $resource->getOwnerId() === $user->id) {
                return true;
            }
            if (method_exists($resource, 'getUserId') && $resource->getUserId() === $user->id) {
                return true;
            }
            if (method_exists($resource, 'getAuthorId') && $resource->getAuthorId() === $user->id) {
                return true;
            }
        }

        if (is_string($permissions)) {
            return $roleService->userHasPermission($user, $permissions);
        }

        foreach ($permissions as $permission) {
            if ($roleService->userHasPermission($user, $permission)) {
                return true;
            }
        }
        
        return false;
    }
}

if (!function_exists('canAny')) {
    function canAny(array $permissions, mixed $resource = null): bool
    {
        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (can($permission, $resource)) {
                return true;
            }
        }
        
        return false;
    }
}

if (!function_exists('canAll')) {
    function canAll(array $permissions, mixed $resource = null): bool
    {
        $user = getCurrentUser();
        if (!$user) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (!can($permission, $resource)) {
                return false;
            }
        }
        
        return true;
    }
}

if (!function_exists('cannot')) {
    function cannot(string|array $permissions, mixed $resource = null): bool
    {
        return !can($permissions, $resource);
    }
}

if (!function_exists('isOwner')) {
    function isOwner(mixed $resource): bool
    {
        $user = getCurrentUser();
        if (!$user || !$resource) {
            return false;
        }

        if (method_exists($resource, 'getOwnerId') && $resource->getOwnerId() === $user->id) {
            return true;
        }
        if (method_exists($resource, 'getUserId') && $resource->getUserId() === $user->id) {
            return true;
        }
        if (method_exists($resource, 'getAuthorId') && $resource->getAuthorId() === $user->id) {
            return true;
        }
        
        return false;
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser(): ?User
    {
        try {
            $session = Container::getInstance()->get(\Forge\Core\Session\SessionInterface::class);
            $userId = $session->get('user_id');
            
            if (!$userId) {
                return null;
            }

            $userRepository = Container::getInstance()->get(\App\Modules\ForgeAuth\Repositories\UserRepository::class);
            return $userRepository->findById($userId);
        } catch (\Exception $e) {
            return null;
        }
    }
}