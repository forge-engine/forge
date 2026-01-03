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
use Forge\Exceptions\ValidationException;
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
            $data = $request->json() ?: $request->postData;
            ForgeAuthValidate::login($data);
            $loginCredentials = $this->sanitize($data);

            $user = $this->forgeAuthService->login($loginCredentials);
            $tokens = $this->forgeAuthService->issueToken($user);

            $responseData = [
                'user' => $user,
                'tokens' => $tokens,
            ];

            return $this->apiResponse($responseData);
        } catch (ValidationException $e) {
            return $this->apiError('Validation failed', 422, [], 'VALIDATION_ERROR');
        } catch (LoginException $e) {
            return $this->apiError('Invalid credentials', 401);
        } catch (\RuntimeException $e) {
            return $this->apiError('JWT is not enabled', 500);
        }
    }

    #[ApiRoute('/auth/refresh', 'POST')]
    public function refresh(Request $request): Response
    {
        $data = $request->json() ?: $request->postData;
        $refreshToken = $data['refresh_token'] ?? null;

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

