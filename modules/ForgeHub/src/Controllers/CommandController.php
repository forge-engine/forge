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
#[Middleware('auth')]
#[Middleware('hub-permissions')]
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

    #[Route("/hub/commands/list")]
    public function getCommands(): Response
    {
        $commands = $this->commandService->getAvailableCommands();
        return new ApiResponse(['commands' => $commands]);
    }

    #[Route("/hub/commands/arguments")]
    public function getCommandArguments(Request $request): Response
    {
        $commandName = $request->query('command') ?? '';
        if (empty($commandName)) {
            return new ApiResponse(['arguments' => []], 400);
        }

        $arguments = $this->commandService->getCommandArguments($commandName);
        return new ApiResponse(['arguments' => $arguments]);
    }

    #[Route("/hub/commands/execute", "POST")]
    public function execute(Request $request): Response
    {
        $command = trim($request->postData['command'] ?? '');

        if (empty($command)) {
            return new ApiResponse([
                'output' => 'Command cannot be empty',
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error',
                'command' => '',
                'commandHistory' => $this->updateCommandHistory('')
            ], 400);
        }

        if (!$this->commandService->isCommandAllowed($command)) {
            return new ApiResponse([
                'output' => 'Command is not allowed',
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error',
                'command' => $command,
                'commandHistory' => $this->updateCommandHistory($command)
            ], 403);
        }

        $commandParts = explode(' ', $command, 2);
        $commandName = $commandParts[0];
        $providedArgs = isset($commandParts[1]) ? explode(' ', $commandParts[1]) : [];

        $validation = $this->commandService->validateCommandArguments($command, $providedArgs);
        if (!$validation['valid']) {
            return new ApiResponse([
                'output' => 'Missing required arguments: ' . implode(', ', $validation['errors']),
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error',
                'command' => $command,
                'commandHistory' => $this->updateCommandHistory($command)
            ], 400);
        }

        $processId = uniqid('cmd_', true);
        $this->updateCommandHistory($command);

        $result = $this->commandService->startCommand($command, $processId);

        return new ApiResponse([
            'output' => $result['output'] ?? '',
            'needsInput' => $result['needsInput'] ?? false,
            'prompt' => $result['prompt'] ?? '',
            'processId' => $processId,
            'status' => $result['status'] ?? 'error',
            'command' => $command,
            'commandHistory' => $_SESSION['command_history'] ?? []
        ]);
    }

    #[Route("/hub/commands/send-input", "POST")]
    public function sendInput(Request $request): Response
    {
        $processId = $request->postData['process_id'] ?? '';
        $input = $request->postData['input'] ?? '';

        if (empty($processId)) {
            return new ApiResponse([
                'output' => 'Process ID is required',
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error'
            ], 400);
        }

        $result = $this->commandService->sendInput($processId, $input);

        return new ApiResponse([
            'output' => $result['output'] ?? '',
            'needsInput' => $result['needsInput'] ?? false,
            'prompt' => $result['prompt'] ?? '',
            'processId' => $processId,
            'status' => $result['status'] ?? 'error',
            'commandHistory' => $_SESSION['command_history'] ?? []
        ]);
    }

    #[Route("/hub/commands/status")]
    public function status(Request $request): Response
    {
        $processId = $request->query('process_id') ?? null;

        if (!$processId) {
            return new ApiResponse(['status' => 'not_found'], 404);
        }

        return new ApiResponse([
            'status' => 'running',
            'processId' => $processId
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
