<?php

declare(strict_types=1);

namespace App\Controllers;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\CookieJar;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Request;
use Forge\Core\Session\SessionInterface;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware("Forge\Core\Http\Middlewares\SessionMiddleware")]
#[Middleware("Forge\Core\Http\Middlewares\CookieMiddleware")]
#[Middleware("Forge\Core\Http\Middlewares\CorsMiddleware")]
#[Middleware("Forge\Core\Http\Middlewares\CompressionMiddleware")]
final class TestController
{
    use ControllerHelper;

    public function __construct(private SessionInterface $session, private CookieJar $cookies)
    {
    }

    #[Route("/test")]
    public function index(Request $request): Response
    {
        $this->session->set("user_id", 123456);
        $cookie = $this->cookies->make('remember_me', 'token123', 60 * 24 * 30);

        $data = [
            "title" => "Welcome to Forge"
        ];

        return $this->view(view: "pages/test/index", data: $data)->withCookie($cookie);
    }
}
