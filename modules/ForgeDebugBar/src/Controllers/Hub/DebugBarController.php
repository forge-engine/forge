<?php

declare(strict_types=1);

namespace App\Modules\ForgeDebugBar\Controllers\Hub;

use App\Modules\ForgeDebugBar\Services\DebugBarHubService;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Middleware('web')]
#[Middleware('auth')]
final class DebugBarController
{
    use ControllerHelper;

    public function __construct(
        private readonly DebugBarHubService $hubService
    ) {
    }

    #[Route(path: "/hub/debugbar")]
    public function index(Request $request): Response
    {
        $latestData = $this->hubService->getLatestData();
        $formattedData = $this->hubService->formatDataForDisplay($latestData);

        $data = [
            'title' => 'Debug Bar',
            'debugData' => $formattedData,
            'hasData' => $latestData !== null,
        ];

        return $this->view(view: "pages/hub/debugbar", data: $data);
    }
}
