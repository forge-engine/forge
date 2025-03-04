<?php

namespace Forge\Modules\ForgeErrorHandler;

use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Core\Contracts\Modules\ErrorHandlerInterface;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{
    private bool $debug;
    private string $logPath;
    private string $basePath;
    private array $hiddenFields = ['password', 'token', 'secret'];

    public function __construct(
        bool   $debugMode = true,
        string $logPath = BASE_PATH . '/storage/logs/errors.log'
    )
    {
        $this->debug = $debugMode;
        $this->logPath = $logPath;
        $this->basePath = BASE_PATH;

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handle(Throwable $e, Request $request): Response
    {
        $this->logError($e, $request);

        if ($this->debug) {
            return $this->debugResponse($e, $request);
        }

        return $this->productionResponse();
    }

    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public function handleException(Throwable $e): void
    {
        $this->handle($e, Request::createFromGlobals())->send();
        exit(1);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handleException(new \ErrorException(
                $error['message'], $error['type'], 0, $error['file'], $error['line']
            ));
        }
    }

    private function debugResponse(Throwable $e, Request $request): Response
    {
        $trace = $e->getTrace();
        $snippets = $this->getCodeSnippets($e);
        $filteredTrace = $this->filterTrace($trace);

        // Add code snippets to the filtered trace
        foreach ($filteredTrace as $index => &$traceItem) {
            $traceItem['code_snippet'] = $snippets[$index] ?? [];
        }
        unset($traceItem); // Unset the reference

        $data = [
            'error' => [
                'message' => $e->getMessage(),
                'type' => get_class($e),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $filteredTrace,
            ],
            'request' => [
                'method' => $request->getMethod(),
                'uri' => $request->getUri(),
                'query' => $this->filterSensitiveData($request->query()->all()),
                'parameters' => $this->filterSensitiveData($request->request()->all()),
                'headers' => $request->getHeaders(),
            ],
            'session' => $this->filterSensitiveData($_SESSION ?? []),
            'environment' => $this->filterSensitiveData($_ENV),
        ];

        return (new Response())
            ->html($this->renderErrorPage($data))
            ->setStatusCode(500);
    }

    /**
     * @return array[]
     */
    private function filterTrace(array $trace): array
    {
        return array_map(function ($item) {
            unset($item['args']);
            return $item;
        }, $trace);
    }


    private function productionResponse(): Response
    {
        return (new Response())
            ->html($this->renderUserFriendlyPage())
            ->setStatusCode(500);
    }

    private function renderErrorPage(array $data): string
    {
        ob_start();
        include __DIR__ . '/views/error_page.php';
        return ob_get_clean();
    }

    private function renderUserFriendlyPage(): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body {
                    font-family: system-ui, sans-serif;
                    background: #f8fafc;
                    padding: 2rem;
                }
                .error-box {
                    max-width: 600px;
                    margin: 2rem auto;
                    padding: 2rem;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>Something Went Wrong</h1>
                <p>We're sorry, but something went wrong. Our team has been notified.</p>
                <p><a href="/">Return to Homepage</a></p>
            </div>
        </body>
        </html>
        HTML;
    }

    private function logError(Throwable $e, Request $request): void
    {
        $log = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n\nRequest: %s %s\n\nSession: %s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
            $request->getMethod(),
            $request->getUri(),
            json_encode($this->filterSensitiveData($_SESSION ?? []))
        );

        file_put_contents($this->logPath, $log, FILE_APPEND);
    }

    private function filterSensitiveData(array $data): array
    {
        foreach ($this->hiddenFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '*****';
            }
        }
        return $data;
    }


    private function getCodeSnippets(Throwable $e): array
    {
        $snippets = [];
        foreach ($e->getTrace() as $frame) {
            $snippets[] = $this->extractCodeSnippet(
                $frame['file'] ?? $e->getFile(),
                $frame['line'] ?? $e->getLine()
            );
        }
        return $snippets;
    }

    /**
     * @return array|array<<missing>,false>
     */
    private function extractCodeSnippet(string $filePath, int $errorLine, int $context = 5): array
    {
        $realFilePath = realpath($filePath);
        if (!$realFilePath || !str_starts_with($realFilePath, $this->basePath)) {
            return [];
        }
        if (!file_exists($filePath)) {
            return [];
        }

        try {
            $fileLines = file($realFilePath, FILE_IGNORE_NEW_LINES);
            if ($fileLines === false) {
                return [];
            }
        } catch (Throwable $e) {
            return [];
        }

        $startLine = max(1, $errorLine - $context);
        $endLine = min(count($fileLines), $errorLine + $context);
        $snippet = [];

        for ($i = $startLine; $i <= $endLine; $i++) {
            $snippet[$i] = $fileLines[$i - 1] ?? '';
        }

        return $snippet;
    }
}