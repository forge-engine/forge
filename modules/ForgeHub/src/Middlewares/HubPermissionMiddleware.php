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
        private readonly ?PermissionService $permissionService = null,
    ) {}

    public function handle(Request $request, callable $next): Response
    {
        $requiredPermissions = $request->getAttribute(
            "required_permissions",
            [],
        );

        if (empty($requiredPermissions)) {
            return $next($request);
        }

        if ($this->permissionService === null) {
            Flash::set("error", "Permission system is not available");
            return Redirect::to("/hub");
        }

        $user = $this->forgeAuthService->user();
        if ($user === null) {
            Flash::set("error", "Authentication required");
            return Redirect::to("/auth/login");
        }

        if (
            !$this->permissionService->hasAnyPermission(
                $user,
                $requiredPermissions,
            )
        ) {
            Flash::set(
                "error",
                "You do not have permission to access this resource",
            );
            return Redirect::to("/hub");
        }

        return $next($request);
    }
}
