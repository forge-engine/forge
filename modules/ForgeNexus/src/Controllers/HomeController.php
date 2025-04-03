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

    #[Route("/nexus/{otp}")]
    public function index(): Response
    {
        return $this->view(view: "pages/index", data: []);
    }
}
