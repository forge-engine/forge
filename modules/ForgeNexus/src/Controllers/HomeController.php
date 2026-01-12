<?php

declare(strict_types=1);

namespace App\Modules\ForgeNexus\Controllers;

use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\State;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Reactive]
#[Middleware('web')]
final class HomeController
{
    use ControllerHelper;

    #[State]
    public int $usersCount = 3;

    #[Action]
    public function refreshUsersCount(): void
    {
        $this->usersCount = $this->usersCount * 2;
    }

    #[Route("/nexus/auth/{otp}")]
    public function index(string $otp): Response
    {
        return $this->view(view: "pages/index");
    }

    #[Route("/nexus/dashboard")]
    public function dashboard(): Response
    {
        $data = [
            'usersCount' => $this->usersCount
        ];
        return $this->view(view: "pages/dashboard", data: $data);
    }
}
