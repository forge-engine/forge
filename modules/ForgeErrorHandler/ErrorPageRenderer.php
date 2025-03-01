<?php

namespace Forge\Modules\ForgeErrorHandler;

use Forge\Core\Helpers\Path;
use Forge\Http\Request;
use Throwable;

class ErrorPageRenderer
{
    private string $basePath;
    private int $snippetContext = 5;

    public function __construct(string $basePath, int $snippetContext = 5)
    {
        $this->basePath = $basePath;
        $this->snippetContext = $snippetContext;
    }

    public function render(Throwable $e, Request $request): string
    {
        $data = [
            'exception' => $e,
            'request' => $request,
            'theme' => $this->getTheme(),
            'styles' => '',
            'code_snippets' => $this->getCodeSnippets($e),
            'stack_trace' => $this->formatStackTrace($e->getTrace()),
            'environment' => $_ENV['APP_ENV'],
        ];

        ob_start();
        extract($data);
        include $this->basePath . '/modules/ForgeErrorHandler/views/error.php';
        return ob_get_clean();
    }

    private function getTheme(): string
    {
        if (isset($_COOKIE['theme'])) {
            return $_COOKIE['theme'] === 'dark' ? 'dark' : 'light';
        }
        return 'light';
    }

    private function getStyles(): string
    {
        $stylesPath = $this->basePath . '/modules/ForgeErrorHandler/styles/error.css';
        if (file_exists($stylesPath)) {
            return file_get_contents($stylesPath);
        }
        return '';
    }

    /**
     * @return array[]
     */
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

    /**
     * @param array<int,mixed> $trace
     * @return array<string,mixed>[]
     */
    private function formatStackTrace(array $trace): array
    {
        $formattedTrace = [];
        foreach ($trace as $index => $frame) {
            $filePath = Path::filePath($frame['file'] ?? '');

            $item = [
                'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''),
                'file' => $filePath ?? null,
                'line' => $frame['line'] ?? null,
                'code_snippet' => []
            ];

            if (isset($frame['file'], $frame['line'])) {
                $item['code_snippet'] = $this->extractCodeSnippet($frame['file'], $frame['line']);
            }

            $formattedTrace[] = $item;
        }
        return $formattedTrace;
    }
}
