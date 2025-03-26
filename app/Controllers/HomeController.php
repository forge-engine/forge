<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Helpers\Flash;
use Forge\Core\Helpers\Redirect;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Request;
use Forge\Exceptions\ValidationException;
use Forge\Traits\ControllerHelper;

#[Service]
final class HomeController
{
    use ControllerHelper;

    public function __construct(private UserRepository $userRepository)
    {
    }

    #[Route("/")]
    public function welcome(Request $request): Response
    {
        $user = $this->userRepository->findById(1);
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
    //#[Middleware('App\Middlewares\AuthMiddleware')]
    public function welcomePost(Request $request): Response
    {
        try {
            $this->validateRegistration($request);

            $this->userRepository->create([
                "username" => $request->postData["username"],
                "password" => password_hash($request->postData["password"], PASSWORD_BCRYPT),
                "email" => $request->postData["email"],
            ]);

            Flash::set("success", "User registered successfully");
            return Redirect::to("/");
        } catch (ValidationException) {
            return Redirect::to("/");
        }
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

    private function validateRegistration(Request $request): void
    {
        $rules = [
            "username" => ["required", "min:3"],
            "email" => ["required", "email", "unique:users,email"],
            "password" => ["required", "min:8"]
        ];

        $customMessages = [
            "required" => "The :field field is required!",
            "min" => "The :field field must be at least :value characters.",
            "unique" => "The :field is already taken."
        ];

        $request->validate($rules, $customMessages);
    }
}
