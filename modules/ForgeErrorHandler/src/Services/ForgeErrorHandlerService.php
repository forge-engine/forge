<?php
declare(strict_types=1);

namespace App\Modules\ForgeErrorHandler\Services;

use App\Modules\ForgeErrorHandler\Contracts\ForgeErrorHandlerInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Http\{Request, Response};
use Forge\Core\Config\Environment;
use Forge\Traits\PathHelper;
use Throwable;

#[Service]
#[Provides(ForgeErrorHandlerInterface::class, version: '0.2.0')]
final class ForgeErrorHandlerService implements
    ForgeErrorHandlerInterface
{
    use PathHelper;

    private bool   $debug;
    private string $basePath;
    private string $logFile;
    private array  $hiddenKeys   = ['password', 'token', 'secret', 'authorization', 'cookie'];
    private array  $rateLimitMap = [];

    private ?object $logger;

    public function __construct(?object $logger = null)
    {
        $this->basePath = BASE_PATH;
        $this->debug    = Environment::getInstance()->isDebugEnabled();
        $this->logFile  = $this->basePath . '/storage/logs/errors.log';
        $this->logger   = $logger && $this->implementsPsr3($logger) ? $logger : null;

        $this->registerHandlers();
    }

    public function handle(Throwable $e, Request $request): Response
    {
        $this->logThrowable($e, $request);

        return $this->debug
            ? $this->buildDebugResponse($e, $request)
            : $this->buildProductionResponse();
    }

    private function registerHandlers(): void
    {
        set_error_handler([$this, 'phpErrorHandler']);
        set_exception_handler([$this, 'phpExceptionHandler']);
        register_shutdown_function([$this, 'phpShutdownHandler']);
    }

    public function phpErrorHandler(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return true;
        }
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public function phpExceptionHandler(Throwable $e): void
    {
        try {
            $request = Request::createFromGlobals();
            $this->handle($e, $request)->send();
        } catch (Throwable $fatal) {
            $this->emergencyOutput($fatal);
        } finally {
            exit(1);
        }
    }

    public function phpShutdownHandler(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }
        $fatals = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
        if (!in_array($error['type'], $fatals, true)) {
            return;
        }
        $this->phpExceptionHandler(
            new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line'])
        );
    }

    private function logThrowable(Throwable $e, Request $request): void
    {
        $fingerprint = $this->fingerprint($e);
        $context     = $this->buildContext($e, $request, $fingerprint);

        if ($this->isRateLimited($fingerprint, 300)) {
            return;
        }

        if ($this->logger) {
            $this->logger->error($e->getMessage(), $context);
        } else {
            $this->fileLog($context);
        }
    }

    private function buildContext(Throwable $e, Request $request, string $fingerprint): array
    {
        $start  = defined('FORGE_START') ? FORGE_START : ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
        $reqId  = $_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(8));
        $source = PHP_SAPI === 'cli'
            ? ['cli' => implode(' ', $_SERVER['argv'] ?? [])]
            : ['ip'   => $this->clientIp(),
                'method' => $request->getMethod(),
                'uri'  => $request->getUri()];

        return [
            'fingerprint' => $fingerprint,
            'request_id'  => $reqId,
            'exception'   => get_class($e),
            'code'        => $e->getCode(),
            'file'        => $e->getFile(),
            'line'        => $e->getLine(),
            'trace'       => $e->getTraceAsString(),
            'memory'      => memory_get_peak_usage(true),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            'source'      => $source,
            'sapi'        => PHP_SAPI,
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'session'     => $this->mask($_SESSION ?? []),
            'get'         => $this->mask($request->queryParams),
            'post'        => $this->mask($request->postData),
        ];
    }

    private function fingerprint(Throwable $e): string
    {
        return substr(md5($e->getFile() . ':' . $e->getLine() . ':' . get_class($e)), 0, 8);
    }

    private function isRateLimited(string $fingerprint, int $seconds): bool
    {
        $now = time();
        if (isset($this->rateLimitMap[$fingerprint]) && ($now - $this->rateLimitMap[$fingerprint]) < $seconds) {
            return true;
        }
        $this->rateLimitMap[$fingerprint] = $now;
        return false;
    }

    private function fileLog(array $context): void
    {
        $dir = dirname($this->logFile);
        is_dir($dir) || mkdir($dir, 0775, true);
        $line = sprintf(
            "[%s] %s [%s] %s – %s:%d | %s\n",
            date('Y-m-d H:i:s'),
            $context['request_id'],
            $context['fingerprint'],
            $context['exception'],
            $context['file'],
            $context['line'],
            str_replace(["\n", "\r"], ' ', $context['trace'])
        );
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    private function buildDebugResponse(Throwable $e, Request $request): Response
    {
        $snippets = $this->extractSnippets($e);
        $trace    = $this->filterTrace($e->getTrace());
        foreach ($trace as $idx => &$frame) {
            $frame['code_snippet'] = $snippets[$idx] ?? [];
        }

        $data = [
            'error'   => [
                'message' => $e->getMessage(),
                'type'    => get_class($e),
                'code'    => $e->getCode(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $trace,
            ],
            'request' => [
                'method'     => $request->getMethod(),
                'uri'        => $request->getUri(),
                'headers'    => $request->getHeaders(),
                'parameters' => $this->mask($request->serverParams),
                'query'      => $this->mask($request->queryParams),
            ],
            'session'     => $this->mask($_SESSION ?? []),
            'environment' => $this->mask($_ENV),
        ];

        return new Response($this->renderErrorPage($data), 500);
    }

    private function buildProductionResponse(): Response
    {
        return new Response($this->renderUserFriendlyPage(), 500);
    }

    private function mask(array $input): array
    {
        array_walk_recursive($input, function (&$v, $k) {
            if (is_string($k) && in_array(strtolower($k), $this->hiddenKeys, true)) {
                $v = '*****';
            }
        });
        return $input;
    }

    private function filterTrace(array $trace): array
    {
        return array_map(fn($f) => array_diff_key($f, ['args' => 0]), $trace);
    }

    private function extractSnippets(Throwable $e): array
    {
        $out = [];
        foreach ($e->getTrace() as $frame) {
            $out[] = $this->codeSnippet($frame['file'] ?? $e->getFile(), $frame['line'] ?? $e->getLine());
        }
        return $out;
    }

    private function codeSnippet(string $file, int $line, int $context = 5): array
    {
        $real = realpath($file);
        if (!$real || !str_starts_with($real, $this->basePath) || !is_file($real)) {
            return [];
        }
        $lines = file($real, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }
        $start = max(1, $line - $context);
        $end   = min(count($lines), $line + $context);
        $slice = [];
        for ($i = $start; $i <= $end; $i++) {
            $slice[$i] = $lines[$i - 1];
        }
        return $slice;
    }

    private function renderErrorPage(array $data): string
    {
        ob_start();
        include __DIR__ . '/../views/error_page.php';
        return ob_get_clean();
    }

    private function renderUserFriendlyPage(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Error</title>
    <style>body{font-family:system-ui,sans-serif;background:#f8fafc;padding:2rem}.box{max-width:600px;margin:2rem auto;padding:2rem;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1)}</style>
</head>
<body>
    <div class="box">
        <h1>Something went wrong</h1>
        <p>We have been notified. Please try again later.</p>
        <p><a href="/">Go home</a></p>
    </div>
</body>
</html>
HTML;
    }

    private function emergencyOutput(Throwable $e): void
    {
        $msg = "Emergency error handler – {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}";
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $msg . PHP_EOL);
        } else {
            http_response_code(500);
            echo nl2br($msg);
        }
    }

    private function clientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    private function implementsPsr3(object $logger): bool
    {
        return method_exists($logger, 'error') && method_exists($logger, 'debug');
    }
}