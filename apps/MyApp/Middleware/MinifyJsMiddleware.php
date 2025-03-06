<?php

namespace MyApp\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Http\Request;
use Forge\Http\Response;
use Closure;

class MinifyJsMiddleware extends MiddlewareInterface
{
    private string $publicPath;
    private string $cachePath;

    public function __construct()
    {
        // Set paths for public directory and JS cache
        $this->publicPath = BASE_PATH . '/public';
        $this->cachePath = $this->publicPath . '/cache/js';
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true); // Create cache directory if it doesn't exist
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Process the request and get the response
        $response = $next($request);

        // Only process HTML responses
        if ($response->getHeader('Content-Type') === 'text/html') {
            $html = $response->getContent();
            $minifiedHtml = $this->processJsReferences($html, $request);
            $response->setContent($minifiedHtml);
        }

        return $response;
    }

    private function processJsReferences(string $html, Request $request): string
    {
        // Find all <script> tags with src attributes
        preg_match_all('/<script\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
        $jsFiles = $matches[1] ?? [];

        foreach ($jsFiles as $index => $jsFile) {
            $originalTag = $matches[0][$index];
            $minifiedJsFile = $this->minifyAndCacheJs($jsFile);

            if ($minifiedJsFile) {
                // Replace the original src with the minified file path
                $newTag = preg_replace(
                    '/src=["\'][^"\']+["\']/i',
                    'src="' . $minifiedJsFile . '"',
                    $originalTag
                );
                $html = str_replace($originalTag, $newTag, $html);
            }
        }

        return $html;
    }

    private function minifyAndCacheJs(string $jsFile): ?string
    {
        // Resolve and validate the source JS file path
        $sourcePath = realpath($this->publicPath . $jsFile);
        if (!$sourcePath || strpos($sourcePath, $this->publicPath) !== 0 || !file_exists($sourcePath)) {
            return null; // Invalid or inaccessible file
        }

        // Define minified file path and URL
        $fileName = basename($jsFile, '.js') . '.min.js';
        $minifiedPath = $this->cachePath . '/' . $fileName;
        $minifiedUrl = '/cache/js/' . $fileName;

        // Serve cached file if it exists and is up-to-date
        if (file_exists($minifiedPath) && filemtime($minifiedPath) >= filemtime($sourcePath)) {
            return $minifiedUrl;
        }

        // Minify the JS content and cache it
        $jsContent = file_get_contents($sourcePath);
        $minifiedJs = $this->minifyJs($jsContent);

        if (file_put_contents($minifiedPath, $minifiedJs) !== false) {
            return $minifiedUrl;
        }

        return null;
    }

    private function minifyJs(string $js): string
    {
        // Remove single-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js);
        // Remove multi-line comments
        $js = preg_replace('/\/\*.*?\*\//s', '', $js);
        // Replace multiple whitespace with a single space
        $js = preg_replace('/\s+/', ' ', $js);
        // Remove spaces around operators
        $js = preg_replace('/\s*([=+\-*/{};:()<>&|])\s*/', '$1', $js);
        return trim($js);
    }
}