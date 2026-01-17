<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeHub\Services\MonitoringService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
#[Middleware('auth')]
#[Middleware('hub-permissions')]
final class MonitoringController
{
    use ControllerHelper;

    public function __construct(
        private readonly MonitoringService $monitoringService
    ) {
    }

    #[Route("/hub/monitoring")]
    public function index(Request $request): Response
    {
        $metrics = $this->monitoringService->getAllMetrics();

        $data = [
            'title' => 'Monitoring',
            'metrics' => $metrics,
        ];

        return $this->view(view: "pages/monitoring", data: $data);
    }

    #[Route("/hub/monitoring/refresh", "POST")]
    public function refresh(Request $request): Response
    {
        $metrics = $this->monitoringService->getAllMetrics();

        return $this->jsonResponse([
            'success' => true,
            'metrics' => $metrics,
        ]);
    }
}
