<?php

declare(strict_types=1);

namespace App\Modules\ForgeErrorHandler\Services;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Requires;
use App\Modules\ForgeErrorHandler\Contracts\ForgeErrorHandlerInterface;
use Forge\Core\Config\Environment;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Traits\PathHelper;
use Throwable;

#[Service]
#[Provides(interface: ForgeErrorHandlerInterface::class, version: '0.1.0')]
#[Requires]
final class ForgeErrorHandlerService implements ForgeErrorHandlerInterface
{
    use PathHelper;

    private bool $debug;
    private string $logPath;
    private string $basePath;
    private array $hiddenFields = ['password', 'token', 'secret'];

    public function __construct()
    {
        $this->logPath = BASE_PATH . "/storage/logs/errors.log";
        $this->basePath = BASE_PATH;
        $this->debug = Environment::getInstance()->isDebugEnabled();

        set_error_handler([$this, "handleError"]);
        set_exception_handler([$this, "handleException"]);
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
        error_log("Exception caught in ForgeErrorHandlerService:");
        error_log("File: " . $e->getFile());
        error_log("Line: " . $e->getLine());
        error_log("Trace:");
        $trace = $e->getTrace();
        for ($i = 0; $i < min(5, count($trace)); $i++) {
            error_log("#" . $i . " " . $trace[$i]['file'] . ":" . $trace[$i]['line'] . " " . $trace[$i]['function'] . "()");
        }
        error_log("Full trace as string:");
        error_log($e->getTraceAsString());
        $this->handle($e, Request::createFromGlobals())->send();
        exit(1);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handleException(new \ErrorException(
                $error['message'],
                $error['type'],
                0,
                $error['file'],
                $error['line']
            ));
        }
    }

    private function debugResponse(Throwable $e, Request $request): Response
    {
        $trace = $e->getTrace();
        $snippets = $this->getCodeSnippets($e);
        $filteredTrace = $this->filterTrace($trace);

        foreach ($filteredTrace as $index => &$traceItem) {
            $traceItem['file'] = $traceItem['file'] ?? '[internal]';
            $traceItem['line'] = $traceItem['line'] ?? 0;
            $traceItem['function'] = $traceItem['function'] ?? '[unknown]';
            $traceItem['args'] = array_map(fn ($arg) => is_object($arg) ? get_class($arg) : gettype($arg), $traceItem['args'] ?? []);
            $traceItem['code_snippet'] = $snippets[$index] ?? [];
        }
        unset($traceItem);

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
                'query' => $this->filterSensitiveData($request->getQuery()),
                'parameters' => $this->filterSensitiveData($request->getQuery()),
                'headers' => $request->getHeaders(),
            ],
            'session' => $this->filterSensitiveData($_SESSION ?? []),
            'environment' => $this->filterSensitiveData($_ENV),
        ];

        return new Response($this->renderErrorPage($data), 500);
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
        return (new Response($this->renderUserFriendlyPage(), 500));
    }

    private function renderErrorPage(array $data): string
    {
        ob_start();
        include dirname(__DIR__, 1) . '/views/error_page.php';
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
