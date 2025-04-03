<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Controllers;

use App\Modules\ForgeHub\Services\CommandService;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\ApiResponse;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
final class CommandController
{
    use ControllerHelper;

    public function __construct(private CommandService $commandService)
    {
    }

    #[Route("/hub/commands")]
    public function index(): Response
    {
        $whoami = trim(shell_exec('whoami') ?? '');
        $pwd = trim(shell_exec('pwd') ?? '');

        return $this->view(view: "pages/commands", data: ['whoami' => $whoami, 'pwd' => $pwd]);
    }

    #[Route("/hub/commands/execute", "POST")]
    public function execute(Request $request): Response
    {
        $command = $request->postData['command'];
        $processId = uniqid('cmd_');
        $_SESSION['commands'][$processId] = ['command' => $command, 'history' => $this->updateCommandHistory($command)];

        $result = $this->commandService->startCommand($command, $processId);
        $_SESSION['commands'][$processId] = array_merge($_SESSION['commands'][$processId], $result);

        return $this->buildResponse($_SESSION['commands'][$processId], $processId);
    }

    #[Route("/hub/commands/send-input", "POST")]
    public function sendInput(Request $request): Response
    {
        $processId = $request->postData['process_id'];
        $input = $request->postData['input'];

        if (!isset($_SESSION['commands'][$processId])) {
            return new Response('Invalid process session.', 400);
        }

        $result = $this->commandService->sendInput($processId, $input);
        $_SESSION['commands'][$processId] = array_merge($_SESSION['commands'][$processId], $result);

        if ($result['status'] === 'completed' || $result['status'] === 'timeout') {
            unset($_SESSION['commands'][$processId]);
        }

        return $this->buildResponse($_SESSION['commands'][$processId], $processId);
    }

    #[Route("/hub/commands/status")]
    public function status(Request $request): Response
    {
        $processId = $request->query('process_id') ?? null;

        if (!$processId || !isset($_SESSION['commands'][$processId])) {
            return new ApiResponse(['status' => 'not_found']);
        }

        return new ApiResponse($_SESSION['commands'][$processId]);
    }

    private function buildResponse(array $result, string $processId): Response
    {
        return new ApiResponse([
                'output' => $result['output'],
                'needsInput' => $result['needsInput'],
                'prompt' => $result['prompt'],
                'processId' => $processId,
                'command' => $_SESSION['commands'][$processId]['command'] ?? '',
                'commandHistory' => $_SESSION['commands'][$processId]['history'] ?? []
            ]);
    }

    private function updateCommandHistory(string $command): array
    {
        $_SESSION['command_history'] = array_slice(
            array_merge($_SESSION['command_history'] ?? [], [$command]),
            -50
        );
        return $_SESSION['command_history'];
    }
}
