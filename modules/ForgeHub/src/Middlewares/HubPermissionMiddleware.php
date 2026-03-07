<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Middlewares;

use App\Modules\ForgeAuth\Services\ForgeAuthService;
use App\Modules\ForgeAuth\Services\PermissionService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use Forge\Core\Helpers\Redirect;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

#[Service]
final class HubPermissionMiddleware extends Middleware
{
    public function __construct(
        private readonly ForgeAuthService $forgeAuthService,
        private readonly ?PermissionService $permissionService = null
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        // Get required permissions from route
        $requiredPermissions = $request->getAttribute('required_permissions', []);

        // If no permissions required, allow access
        if (empty($requiredPermissions)) {
            return $next($request);
        }

        // If permission service is not available, deny access (fail secure)
        if ($this->permissionService === null) {
            Flash::set('error', 'Permission system is not available');
            return Redirect::to('/hub');
        }

        // Get current user
        $user = $this->forgeAuthService->user();
        if ($user === null) {
            // This shouldn't happen if AuthMiddleware ran first, but fail secure
            Flash::set('error', 'Authentication required');
            return Redirect::to('/auth/login');
        }

        // Check if user has any of the required permissions
        if (!$this->permissionService->hasAnyPermission($user, $requiredPermissions)) {
            Flash::set('error', 'You do not have permission to access this resource');
            return Redirect::to('/hub');
        }

        return $next($request);
    }
}
