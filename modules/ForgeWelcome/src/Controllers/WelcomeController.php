<?php

declare(strict_types=1);

namespace App\Modules\ForgeWelcome\Controllers;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class WelcomeController
{
    use ControllerHelper;

    #[Route("/")]
    public function index(): Response
    {
        return $this->view(view: "pages/index", data: []);
    }
}
