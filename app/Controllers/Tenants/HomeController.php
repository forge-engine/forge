<?php

declare(strict_types=1);

namespace App\Controllers\Tenants;

use App\Models\Post;
use App\Modules\ForgeAuth\Services\ForgeAuthService;
use App\Modules\ForgeAuth\Validation\ForgeAuthValidate;
use App\Modules\ForgeMultiTenant\Attributes\TenantScope;
use App\Services\UserService;
use Exception;
use Forge\Core\Debug\Metrics;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Debuger;
use Forge\Core\Helpers\Flash;
use Forge\Core\Helpers\Redirect;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Request;
use Forge\Exceptions\ValidationException;
use Forge\Traits\ControllerHelper;
use Forge\Traits\SecurityHelper;

#[Service]
#[TenantScope('tenant')]
#[Middleware("web")]
final class HomeController
{
  use ControllerHelper;
  use SecurityHelper;

  public function __construct(
    public readonly ForgeAuthService $forgeAuthService,
    public readonly UserService $userService,
  ) {
  }

  #[Route("/")]
  public function index(): Response
  {
    Metrics::start("db_load_one_record_test");
    $posts = [];
    Metrics::stop("db_load_one_record_test");

    $data = [
      "title" => "Welcome to Forge Framework",
      "posts" => $posts,
    ];

    return $this->view(view: "pages/tenants/index", data: $data);
  }

  #[Route("/app", "POST")]
  public function register(Request $request): Response
  {
    try {
      ForgeAuthValidate::register($request->postData);
      $credentials = $this->sanitize($request->postData);
      $this->forgeAuthService->register($credentials);

      Flash::set("success", "User registered successfully");
      return Redirect::to("/");
    } catch (ValidationException) {
      return Redirect::to("/");
    }
  }

  #[Route("/app/{id}", "PATCH")]
  #[Middleware("App\Modules\ForgeAuth\Middlewares\AuthMiddleware")]
  public function updateUser(Request $request, string $id): Response
  {
    $id = (int) $id;
    $data = [
      "identifier" => $request->postData["identifier"],
      "email" => $request->postData["email"],
    ];
    //$this->userRepository->update($id, $data);

    return new Response("<h1> Successfully updated!</h1>", 401);
  }
}
