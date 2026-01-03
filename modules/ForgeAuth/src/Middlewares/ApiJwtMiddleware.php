<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Middlewares;

use App\Modules\ForgeAuth\Exceptions\JwtTokenExpiredException;
use App\Modules\ForgeAuth\Exceptions\JwtTokenInvalidException;
use App\Modules\ForgeAuth\Exceptions\JwtTokenMissingException;
use App\Modules\ForgeAuth\Services\ForgeAuthService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\ApiResponse;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

#[Service]
final class ApiJwtMiddleware extends Middleware
{
    public function __construct(private readonly ForgeAuthService $forgeAuthService)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $authHeader = $request->getHeader('Authorization');
        if (!$authHeader) {
            return $this->unauthorizedResponse('Unauthorized: Missing token');
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorizedResponse('Unauthorized: Invalid token');
        }

        $token = substr($authHeader, 7);
        if (empty($token)) {
            return $this->unauthorizedResponse('Unauthorized: Missing token');
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return $this->unauthorizedResponse('Unauthorized: Invalid token');
        }

        try {
            $user = $this->forgeAuthService->resolveUserFromToken($token);
        } catch (JwtTokenMissingException | JwtTokenInvalidException | JwtTokenExpiredException $e) {
            return $this->unauthorizedResponse($e->getMessage());
        }

        if (!$user) {
            return $this->unauthorizedResponse('Unauthorized: Invalid token');
        }

        $this->forgeAuthService->setUser($user);

        return $next($request);
    }

    private function unauthorizedResponse(string $message): Response
    {
        return new ApiResponse(
            null,
            401,
            [],
            [
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => $message,
                    'errors' => [],
                ],
            ]
        );
    }
}

