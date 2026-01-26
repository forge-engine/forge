<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Middlewares;

use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeAuth\Services\RoleService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

#[Service]
final class RoleMiddleware
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

        $requiredRoles = $request->getAttribute('required_roles') ?? [];
        if (empty($requiredRoles)) {
            return $next($request);
        }

        foreach ($requiredRoles as $role) {
            if ($this->roleService->userHasRole($user, $role)) {
                return $next($request);
            }
        }

        return new Response('Forbidden - Insufficient role permissions', 403);
    }

    private function getCurrentUser(Request $request): ?User
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');
        
        if (!$userId) {
            return null;
        }

        $userRepository = \Forge\Core\DI\Container::getInstance()->get(\App\Modules\ForgeAuth\Repositories\UserRepository::class);
        return $userRepository->findById($userId);
    }
}