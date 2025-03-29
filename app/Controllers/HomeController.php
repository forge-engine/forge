<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\ForgeAuth\Repositories\UserRepository;
use App\Modules\ForgeAuth\Services\ForgeAuthService;
use Forge\Core\DI\Attributes\Service;
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
#[Middleware('web')]
final class HomeController
{
    use ControllerHelper;
    use SecurityHelper;

    public function __construct(private UserRepository $userRepository, private ForgeAuthService $forgeAuthService)
    {
    }

    #[Route("/")]
     public function index(): Response
     {
         $user = $this->userRepository->findById(1);
         $data = [
                "title" => "Welcome to Forge Framework",
                "user" => $user
          ];


         return $this->view(view: "pages/home/index", data: $data);
     }

    #[Route("/", "POST")]
     public function register(Request $request): Response
     {
         try {
             $this->validateRegistration($request);
             $credentials = $this->sanitize($request->postData);

             $this->forgeAuthService->register($credentials);

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
