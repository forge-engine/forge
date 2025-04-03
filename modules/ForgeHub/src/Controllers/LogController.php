<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeHub\Services\LogService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class LogController
{
    use ControllerHelper;

    public function __construct(private LogService $logService)
    {
    }

    #[Route("/hub/logs")]
    public function index(Request $request): Response
    {
        $data = [
            'files' => $this->logService->getLogFiles(),
            'entries' => $this->logService->getLogEntries(
                $request->query('file', 'error.log'),
                $request->query('search'),
                $request->query('date')
            )
        ];

        return $this->view(view: "pages/logs", data: $data);
    }
}
