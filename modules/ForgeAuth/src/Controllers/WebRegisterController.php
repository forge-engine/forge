<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Controllers;

use App\Modules\ForgeAuth\Exceptions\UserRegistrationException;
use App\Modules\ForgeAuth\Services\ForgeAuthService;
use App\Modules\ForgeAuth\Services\RedirectHandlerService;
use App\Modules\ForgeAuth\Validation\ForgeAuthValidate;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use Forge\Core\Helpers\Redirect;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;
use Forge\Traits\SecurityHelper;

#[Service]
#[Middleware('web')]
final class WebRegisterController
{
  use ControllerHelper;
  use SecurityHelper;

  public function __construct(private ForgeAuthService $forgeAuthService, private RedirectHandlerService $redirectHandlerService)
  {
  }

  #[Route("/auth/register")]
  public function index(): Response
  {
    return $this->view(view: "pages/register");
  }

  #[Route("/auth/register", "POST")]
  public function register(Request $request): Response
  {
    try {
      ForgeAuthValidate::register($request->postData);
      $registerData = $this->sanitize($request->postData);

      $this->forgeAuthService->register($registerData);
      Flash::set("success", "Registration successful. Please login.");
      return Redirect::to($this->redirectHandlerService->redirectAfterLogin());
    } catch (UserRegistrationException) {
      Flash::set("error", "Registration failed. Please try again.");
      return Redirect::to('/auth/register');
    } catch (\Exception $e) {
      Flash::set("error", $e->getMessage());
      return Redirect::to('/auth/register');
    }
  }
}
