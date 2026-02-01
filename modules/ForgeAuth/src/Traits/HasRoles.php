<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Traits;

use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeAuth\Services\RoleService;
use Forge\Core\DI\Container;
use Forge\Core\Http\Response;

trait HasRoles
{
    private function getRoleService(): RoleService
    {
        return Container::getInstance()->get(RoleService::class);
    }

    /**
     * Authorize that the current user has the given permission.
     *
     * @param string|\BackedEnum $permission The permission to check
     * @return void
     */
    public function authorize(string|\BackedEnum $permission): void
    {
        $permissionName = $permission instanceof \BackedEnum ? (string) $permission->value : $permission;

        if (!method_exists($this, 'getCurrentUser')) {
             throw new \RuntimeException('The class using HasRoles must also use HasCurrentUser or implement getCurrentUser() to use authorize().');
        }

        $user = $this->getCurrentUser();

        if (!$user) {
             ob_start();
             $errorCode = 401;
             require BASE_PATH . "/engine/Templates/Views/error_page.php";
             $content = ob_get_clean();
             (new Response($content, 401))->send();
             exit;
        }

        if (!$this->hasPermission($user, $permissionName)) {
             ob_start();
             $errorCode = 403;
             require BASE_PATH . "/engine/Templates/Views/error_page.php";
             $content = ob_get_clean();
             (new Response($content, 403))->send();
             exit;
        }
    }

    /**
     * Authorize that the current user has ANY of the given permissions.
     *
     * @param array<string|\BackedEnum> $permissions List of permissions
     * @return void
     */
    public function authorizeAny(array $permissions): void
    {
        // Convert enums to strings if needed
        $permissionNames = array_map(
            fn($p) => $p instanceof \BackedEnum ? (string) $p->value : $p,
            $permissions
        );

        if (!method_exists($this, 'getCurrentUser')) {
             throw new \RuntimeException('The class using HasRoles must also use HasCurrentUser or implement getCurrentUser() to use authorizeAny().');
        }

        $user = $this->getCurrentUser();

        if (!$user) {
             ob_start();
             $errorCode = 401;
             require BASE_PATH . "/engine/Templates/Views/error_page.php";
             $content = ob_get_clean();
             (new Response($content, 401))->send();
             exit;
        }

        if (!$this->hasAnyPermission($user, $permissionNames)) {
             ob_start();
             $errorCode = 403;
             require BASE_PATH . "/engine/Templates/Views/error_page.php";
             $content = ob_get_clean();
             (new Response($content, 403))->send();
             exit;
        }
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

    public function can(
        User $user,
        string $permission,
        mixed $resource = null,
    ): bool {
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

        return $this->hasPermission($user, $permission);
    }

    public function canAny(
        User $user,
        array $permissions,
        mixed $resource = null,
    ): bool {
        foreach ($permissions as $permission) {
            if ($this->can($user, $permission, $resource)) {
                return true;
            }
        }
        return false;
    }

    public function canAll(
        User $user,
        array $permissions,
        mixed $resource = null,
    ): bool {
        foreach ($permissions as $permission) {
            if (!$this->can($user, $permission, $resource)) {
                return false;
            }
        }
        return true;
    }

    public function cannot(
        User $user,
        string $permission,
        mixed $resource = null,
    ): bool {
        return !$this->can($user, $permission, $resource);
    }

    public function isOwner(User $user, mixed $resource): bool
    {
        if (!$resource) {
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
