<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Controllers;

use App\Modules\ForgeAuth\Exceptions\LoginException;
use App\Modules\ForgeAuth\Services\ForgeAuthService;
use App\Modules\ForgeAuth\Validation\ForgeAuthValidate;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Redirect;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;
use Forge\Traits\SecurityHelper;

#[Service]
#[Middleware('web')]
final class WebLoginController
{
    use ControllerHelper;
    use SecurityHelper;

    public function __construct(private ForgeAuthService $forgeAuthService)
    {
    }

    #[Route("/auth/login")]
    public function index(): Response
    {
        return $this->view(view: "pages/login");
    }

    #[Route("/auth/login", "POST")]
    public function login(Request $request): Response
    {
        try {
            ForgeAuthValidate::login($request->postData);
            $loginCredentials = $this->sanitize($request->postData);

            $this->forgeAuthService->login($loginCredentials);

            return Redirect::to("/dashboard");
        } catch (LoginException) {
            return Redirect::to('/auth/login');
        }
    }

    #[Route('/auth/logout', 'POST')]
    public function logout(): Response
    {
        $this->forgeAuthService->logout();
        return Redirect::to("/");
    }
}
