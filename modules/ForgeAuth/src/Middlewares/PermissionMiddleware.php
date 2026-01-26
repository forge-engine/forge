<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Middlewares;

use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeAuth\Services\RoleService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

#[Service]
final class PermissionMiddleware
{
    public function __construct(
        private readonly RoleService $roleService
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $user = $this->getCurrentUser($request);
        
        if (!$user) {
            return new Response('Unauthorized', 401);
        }

        $requiredPermissions = $request->getAttribute('required_permissions') ?? [];
        if (empty($requiredPermissions)) {
            return $next($request);
        }

        foreach ($requiredPermissions as $permission) {
            if ($this->roleService->userHasPermission($user, $permission)) {
                return $next($request);
            }
        }

        return new Response('Forbidden - Insufficient permissions', 403);
    }

    private function getCurrentUser(Request $request): ?User
    {
        try {
            $session = \Forge\Core\DI\Container::getInstance()->get(\Forge\Core\Session\SessionInterface::class);
            $userId = $session->get('user_id');
            
            if (!$userId) {
                return null;
            }

            $userRepository = \Forge\Core\DI\Container::getInstance()->get(\App\Modules\ForgeAuth\Repositories\UserRepository::class);
            return $userRepository->findById($userId);
        } catch (\Exception $e) {
            return null;
        }
    }
}