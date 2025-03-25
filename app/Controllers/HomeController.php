<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Request;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware("Forge\Core\Http\Middlewares\SessionMiddleware")]
#[Middleware("Forge\Core\Http\Middlewares\CookieMiddleware")]
#[Middleware("Forge\Core\Http\Middlewares\CorsMiddleware")]
#[Middleware("Forge\Core\Http\Middlewares\CompressionMiddleware")]
final class HomeController
{
    use ControllerHelper;

    public function __construct(private UserRepository $userRepository)
    {
    }

    #[Route("/")]
    public function welcome(Request $request): Response
    {
        $user = $this->userRepository->findAll();
        $data = [
            "title" => "Welcome to Forge Framework",
            "alerts" => [
                ["type" => "success", "message" => "Installation successful!"],
                ["type" => "info", "message" => "New features available"],
            ],
            "user" => $user
        ];


        return $this->view(view: "pages/home/index", data: $data);
    }

    #[Route("/", "POST")]
    public function welcomePost(Request $request): Response
    {
        $data = [
            "username" => $request->postData["username"],
            "password" => $request->postData["password"],
            "email" => $request->postData["email"],
        ];
        $this->userRepository->create($data);

        return $this->jsonResponse(['success' => true]);
    }

    #[Route("/{id}", "PATCH")]
    #[Middleware("App\Middlewares\AuthMiddleware")]
    public function updateUser(Request $request, array $params): Response
    {
        $id = (int)$params["id"];
        $data = [
            "username" => $request->postData["username"],
            "email" => $request->postData["email"],
        ];
        $this->userRepository->update($id, $data);

        return new Response("<h1> Successfully updated!</h1>");
    }

    #[Route("/users")]
    public function users(Request $request): Response
    {
        echo "<pre>";
        print_r($request);
        echo "</pre>";
        return new Response("Users");
    }

    #[Route("/users/{id}")]
    public function user(Request $request, array $params): Response
    {
        echo "<pre>";
        print_r($request);
        print_r($params);
        echo "</pre>";
        return new Response("User id {$params["id"]}");
    }
}
