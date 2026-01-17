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
#[Middleware('auth')]
#[Middleware('hub-permissions')]
final class LogController
{
    use ControllerHelper;

    public function __construct(private LogService $logService)
    {
    }

    #[Route("/hub/logs")]
    public function index(Request $request): Response
    {
        $selectedFile = $request->query('file');
        $entries = [];
        $error = null;

        if ($selectedFile) {
            try {
                // Convert Generator to array for the view
                foreach ($this->logService->getLogEntries(
                    $selectedFile,
                    $request->query('search'),
                    $request->query('date')
                ) as $entry) {
                    $entries[] = $entry;
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $data = [
            'files' => $this->logService->getLogFiles(),
            'entries' => $entries,
            'error' => $error,
            'selectedFile' => $selectedFile,
        ];

        return $this->view(view: "pages/logs", data: $data);
    }
}
