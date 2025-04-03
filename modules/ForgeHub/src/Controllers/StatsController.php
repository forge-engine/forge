<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeHub\Services\LogService;
use Forge\Core\Debug\Metrics;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class StatsController
{
    use ControllerHelper;

    public function __construct(private LogService $logService)
    {
    }

    #[Route("/hub/stats")]
    public function index(): Response
    {
        $data = [
            'metrics' => Metrics::get()
        ];

        return $this->view(view: "pages/stats", data: $data);
    }
}
