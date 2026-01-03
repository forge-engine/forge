<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Controllers;

use App\Modules\ForgeAuth\Exceptions\LoginException;
use App\Modules\ForgeAuth\Services\ForgeAuthService;
use App\Modules\ForgeAuth\Validation\ForgeAuthValidate;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\ApiRoute;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Traits\ControllerHelper;
use Forge\Traits\SecurityHelper;

#[Service]
#[Middleware('api')]
final class ApiLoginController
{
    use ControllerHelper;
    use SecurityHelper;

    public function __construct(private readonly ForgeAuthService $forgeAuthService)
    {
    }

    #[ApiRoute('/auth/login', 'POST')]
    public function login(Request $request): Response
    {
        try {
            ForgeAuthValidate::login($request->postData);
            $loginCredentials = $this->sanitize($request->postData);

            $user = $this->forgeAuthService->login($loginCredentials);
            $tokens = $this->forgeAuthService->issueToken($user);

            $data = [
                'user' => $user,
                'tokens' => $tokens,
            ];

            return $this->apiResponse($data);
        } catch (LoginException $e) {
            return $this->apiError('Invalid credentials', 401);
        } catch (\RuntimeException $e) {
            return $this->apiError('JWT is not enabled', 500);
        }
    }

    #[ApiRoute('/auth/refresh', 'POST')]
    public function refresh(Request $request): Response
    {
        $refreshToken = $request->postData['refresh_token'] ?? null;

        if (!$refreshToken) {
            return $this->apiError('Refresh token is required', 400);
        }

        $tokens = $this->forgeAuthService->refreshToken($refreshToken);

        if (!$tokens) {
            return $this->apiError('Invalid refresh token', 401);
        }

        return $this->apiResponse($tokens);
    }
}

