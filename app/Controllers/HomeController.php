<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Request;

#[Service]
class HomeController
{
    public function __construct(private UserRepository $userRepository) {}

    #[Route("/")]
    public function welcome(Request $request): string
    {
        $user = $this->userRepository->findAll();
        $data = [
            "title" => "Welcome to Forge Framework",
            "alerts" => [
                ["type" => "success", "message" => "Installation successful!"],
                ["type" => "info", "message" => "New features available"],
            ],
            "user" => $user,
        ];
        return view("home/index", $data);
    }

    #[Route("/", "POST")]
    #[Middleware("App\Middlewares\AuthMiddleware")]
    public function welcomePost(Request $request): string
    {
        $data = [
            "username" => $request->postData["username"],
            "password" => $request->postData["password"],
            "email" => $request->postData["email"],
        ];
        $this->userRepository->create($data);

        return "<h1> Successfully registered!</h1>";
    }

    #[Route("/{id}", "PATCH")]
    #[Middleware("App\Middlewares\AuthMiddleware")]
    public function updateUser(Request $request, array $params): string
    {
        $id = (int) $params["id"];
        $data = [
            "username" => $request->postData["username"],
            "email" => $request->postData["email"],
        ];
        $this->userRepository->update($id, $data);

        return "<h1> Successfully updated!</h1>";
    }

    #[Route("/users")]
    public function users(Request $request): string
    {
        echo "<pre>";
        print_r($request);
        echo "</pre>";
        return "Users";
    }

    #[Route("/users/{id}")]
    public function user(Request $request, array $params): string
    {
        echo "<pre>";
        print_r($request);
        print_r($params);
        echo "</pre>";
        return "User id {$params["id"]}";
    }
}
