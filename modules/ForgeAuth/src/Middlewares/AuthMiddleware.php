<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Middlewares;

use App\Modules\ForgeAuth\Services\ForgeAuthService;
use App\Modules\ForgeAuth\Services\RedirectHandlerService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Redirect;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

#[Service]
final class AuthMiddleware extends Middleware
{
    public function __construct(
        private readonly ForgeAuthService $forgeAuthService,
        private readonly RedirectHandlerService $redirectHandler
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!$this->forgeAuthService->user()) {
            // Store the intended URL before redirecting to login
            $intendedUrl = $request->serverParams['REQUEST_URI'] ?? '/';
            $this->redirectHandler->storeIntendedUrl($intendedUrl);

            return Redirect::to("/auth/login", 401);
        }

        return $next($request);
    }
}
