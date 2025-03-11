<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Request;

#[Service]
class HomeController
{
    public function __construct() {}

    #[Route("/")]
    public function welcome(Request $request): string
    {
        $user = User::find(1);
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
