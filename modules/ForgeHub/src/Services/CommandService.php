<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class CommandService
{
    private const PROCESS_TIMEOUT = 30; // seconds
    private const BUFFER_READ_SIZE = 4096;

    public mixed $process = null;
    public array $pipes = [];
    public string $outputBuffer = '';
    public ?string $processId = null;
    private array $processes = [];

    public function startCommand(string $command, string $processId): array
    {
        $this->processId = $processId;
        $forgePath = BASE_PATH . '/forge.php';
        $fullCommand = "php {$forgePath} " . escapeshellcmd($command);
        $descriptorspec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $this->process = proc_open($fullCommand, $descriptorspec, $this->pipes);
        $this->outputBuffer = '';

        if (!is_resource($this->process)) {
            return [
                'output' => 'Error starting process',
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error'
            ];
        }

        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);

        return $this->readProcessOutput();
    }

    public function sendInput(string $processId, string $input): array
    {
        if (!isset($this->processes[$processId])) {
            return [
                'output' => 'No active process found for this ID.',
                'needsInput' => false,
                'prompt' => '',
                'status' => 'error'
            ];
        }

        $processInfo = $this->processes[$processId];
        $process = $processInfo['process'];
        $pipes = $processInfo['pipes'];

        if (is_resource($process) && is_resource($pipes[0])) {
            fwrite($pipes[0], $input . PHP_EOL);
            fflush($pipes[0]);
            return $this->readProcessOutput($processId, $process, $pipes);
        }

        return [
            'output' => 'No active process or stdin pipe available.',
            'needsInput' => false,
            'prompt' => '',
            'status' => 'error'
        ];
    }

    private function readProcessOutput(): array
    {
        $needsInput = false;
        $prompt = '';
        $startTime = time();
        $outputBuffer = '';
        $lastOutputTime = time();

        while (true) {
            $read = [$this->pipes[1], $this->pipes[2]];
            $write = null;
            $except = null;

            $numStreams = @stream_select($read, $write, $except, 1);

            if ($numStreams > 0) {
                $lastOutputTime = time();
                if (in_array($this->pipes[1], $read)) {
                    $output = stream_get_contents($this->pipes[1]);
                    if ($output !== false) {
                        error_log("STDOUT for process {$this->processId}: " . trim($output));
                        $outputBuffer .= $output;
                    }
                }
                if (in_array($this->pipes[2], $read)) {
                    $errorOutput = stream_get_contents($this->pipes[2]);
                    if ($errorOutput !== false) {
                        error_log("STDERR for process {$this->processId}: " . trim($errorOutput));
                        $outputBuffer .= $errorOutput;
                    }
                }

                // Check for prompt
                if (!$needsInput) {
                    $detectedPrompt = $this->detectPrompt($outputBuffer);
                    if ($detectedPrompt !== null) {
                        error_log("Detected Prompt for process {$this->processId}: " . $detectedPrompt);
                        $prompt = $detectedPrompt;
                        $needsInput = true;
                        return $this->buildResponse($needsInput, $prompt, 'waiting_for_input', $outputBuffer);
                    }
                }
            }

            $status = proc_get_status($this->process);
            if (!$status['running']) {
                $this->cleanupProcess();
                return $this->buildResponse($needsInput, $prompt, 'completed', $outputBuffer);
            }

            // Inactivity timeout check
            if (time() - $lastOutputTime > self::PROCESS_TIMEOUT) {
                proc_terminate($this->process);
                $outputBuffer .= "\nProcess timed out due to inactivity.";
                $this->cleanupProcess();
                return $this->buildResponse(false, '', 'timeout', $outputBuffer);
            }

            // Add a small sleep here to reduce CPU usage if no output
            usleep(100000);
        }
    }


    private function detectPrompt(string $buffer): ?string
    {
        $patterns = [
            '/([a-zA-Z0-9_\-]+[>:]\s*)$/',
            '/([?]\s*)$/',
            '/\[([^\]]+)\]\s*$/',
            '/(password|passphrase|credentials?)[^:]*:\s*$/i',
            '/((enter|type|input)[^:]*:)\s*$/i',
            '/(\.\.\.|>+)\s*$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $buffer, $matches)) {
                return trim($matches[1]);
            }
        }
        return null;
    }

    private function cleanupProcess(): void
    {
        if (is_resource($this->process)) {
            foreach ($this->pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_close($this->process);
            $this->process = null;
            $this->pipes = [];
            $this->processId = null;
        }
    }

    private function buildResponse(bool $needsInput, string $prompt, string $status, string $outputBuffer): array
    {
        return [
            'output' => $outputBuffer,
            'needsInput' => $needsInput,
            'prompt' => $prompt,
            'status' => $status,
        ];
    }
}
