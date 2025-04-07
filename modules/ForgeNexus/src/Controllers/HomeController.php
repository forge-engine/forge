<?php

declare(strict_types=1);

namespace App\Modules\ForgeNexus\Controllers;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class HomeController
{
    use ControllerHelper;

    #[Route("/nexus/auth/{otp}")]
    public function index(string $otp): Response
    {
        return $this->view(view: "pages/index", data: []);
    }

    #[Route("/nexus/dashboard")]
    public function dashboard(): Response
    {
        return $this->view(view: "pages/dashboard", data: []);
    }
}
