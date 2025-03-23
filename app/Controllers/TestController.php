<?php

declare(strict_types=1);

namespace App\Controllers;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Request;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware("Forge\Core\Http\Middlewares\CorsMiddleware")]
#[Middleware("Forge\Core\Http\Middlewares\CompressionMiddleware")]
final class TestController
{
    use ControllerHelper;

    public function __construct(/** private UserRepository $userRepository */)
    {
    }

    #[Route("/test")]
    public function index(Request $request): Response
    {
        $data = [
            "title" => "Welcome to Forge"
        ];

        return $this->view(view: "pages/test/index", data: $data);
    }
}
