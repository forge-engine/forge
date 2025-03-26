<?php

declare(strict_types=1);

namespace App\Modules\ExampleModule\Controllers;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Core\Http\Request;
use Forge\Traits\ControllerHelper;

#[Service]
final class ExampleController
{
    use ControllerHelper;

    #[Route("/example-module")]
    public function index(Request $request): Response
    {
        $data = [
            "title" => "Welcome to Forge"
        ];

        return $this->view(view: "pages/example", data: $data);
    }
}
