<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeAuth\Services\ForgeAuthService;
use App\Modules\ForgeMultiTenant\Attributes\TenantScope;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;
use Forge\Traits\SecurityHelper;

#[Service]
#[TenantScope("tenant")]
#[Middleware("web")]
final class DashboardController
{
  use ControllerHelper;
  use SecurityHelper;

  public function __construct(private readonly ForgeAuthService $authService)
  {
    //
  }

  #[Route("/dashboard")]
  #[Middleware("\App\Modules\ForgeAuth\Middlewares\AuthMiddleware::class")]
  public function welcome(): Response
  {
    $data = [
      "title" => "Welcome to Forge Framework",
      "user" => $this->authService->user() ?? [],
    ];

    return $this->view(view: "pages/dashboard/index", data: $data);
  }
}
