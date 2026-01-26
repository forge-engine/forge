<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Traits;

use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeAuth\Services\RoleService;
use Forge\Core\DI\Container;

trait HasRoles
{
    private function getRoleService(): RoleService
    {
        return Container::getInstance()->get(RoleService::class);
    }

    public function hasRole(User $user, string $roleName): bool
    {
        return $this->getRoleService()->userHasRole($user, $roleName);
    }

    public function hasAnyRole(User $user, array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if ($this->hasRole($user, $roleName)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllRoles(User $user, array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if (!$this->hasRole($user, $roleName)) {
                return false;
            }
        }
        return true;
    }

    public function hasPermission(User $user, string $permission): bool
    {
        return $this->getRoleService()->userHasPermission($user, $permission);
    }

    public function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }
        return true;
    }

    public function can(User $user, string $permission, mixed $resource = null): bool
    {
        // Resource-level permission check with ownership
        if ($resource) {
            if (method_exists($resource, 'getOwnerId') && $resource->getOwnerId() === $user->id) {
                return true;
            }
            
            // Check for additional ownership methods
            if (method_exists($resource, 'getUserId') && $resource->getUserId() === $user->id) {
                return true;
            }
            
            if (method_exists($resource, 'getAuthorId') && $resource->getAuthorId() === $user->id) {
                return true;
            }
        }

        return $this->hasPermission($user, $permission);
    }

    public function canAny(User $user, array $permissions, mixed $resource = null): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($user, $permission, $resource)) {
                return true;
            }
        }
        return false;
    }

    public function canAll(User $user, array $permissions, mixed $resource = null): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->can($user, $permission, $resource)) {
                return false;
            }
        }
        return true;
    }

    public function cannot(User $user, string $permission, mixed $resource = null): bool
    {
        return !$this->can($user, $permission, $resource);
    }

    public function isOwner(User $user, mixed $resource): bool
    {
        if (!$resource) {
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