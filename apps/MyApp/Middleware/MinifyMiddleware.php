<?php

namespace MyApp\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Http\Request;
use Forge\Http\Response;
use Closure;

class MinifyMiddleware extends MiddlewareInterface
{
    private string $publicPath;
    private string $cachePath;

    public function __construct()
    {
        $this->publicPath = BASE_PATH . '/public';
        $this->cachePath = $this->publicPath . '/cache/css';
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Handle the request and minify HTML and CSS.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getHeader('Content-Type') === 'text/html') {
            $html = $response->getContent();
            $minifiedHtml = $this->minifyHtml($html);
            $minifiedHtmlWithCss = $this->processCssReferences($minifiedHtml, $request);
            $response->setContent($minifiedHtmlWithCss);
        }

        return $response;
    }

    /**
     * Minify HTML content.
     */
    private function minifyHtml(string $html): string
    {
        // Remove HTML comments (except conditional comments)
        $html = preg_replace('/<!--[^[>](.*?)?-->/s', '', $html);
        // Replace multiple whitespace with a single space
        $html = preg_replace('/\s+/', ' ', $html);
        // Remove spaces between tags
        $html = preg_replace('/> </', '><', $html);
        return trim($html);
    }

    /**
     * Process CSS references in HTML, minify CSS files, and update links.
     */
    private function processCssReferences(string $html, Request $request): string
    {
        preg_match_all('/<link\s+[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
        $cssFiles = $matches[1] ?? [];

        foreach ($cssFiles as $index => $cssFile) {
            $originalTag = $matches[0][$index];
            $minifiedCssFile = $this->minifyAndCacheCss($cssFile);

            if ($minifiedCssFile) {
                $newTag = preg_replace(
                    '/href=["\'][^"\']+["\']/i',
                    'href="' . $minifiedCssFile . '"',
                    $originalTag
                );
                $html = str_replace($originalTag, $newTag, $html);
            }
        }

        return $html;
    }

    /**
     * Minify a CSS file and cache it, returning the new file path.
     */
    private function minifyAndCacheCss(string $cssFile): ?string
    {
        $sourcePath = realpath($this->publicPath . $cssFile);
        if (!$sourcePath || strpos($sourcePath, $this->publicPath) !== 0 || !file_exists($sourcePath)) {
            return null;
        }

        $fileName = basename($cssFile, '.css') . '.min.css';
        $minifiedPath = $this->cachePath . '/' . $fileName;
        $minifiedUrl = '/cache/css/' . $fileName;

        if (file_exists($minifiedPath) && filemtime($minifiedPath) >= filemtime($sourcePath)) {
            return $minifiedUrl;
        }

        $cssContent = file_get_contents($sourcePath);
        $minifiedCss = $this->minifyCss($cssContent);

        if (file_put_contents($minifiedPath, $minifiedCss) !== false) {
            return $minifiedUrl;
        }

        return null;
    }

    /**
     * Minify CSS content.
     */
    private function minifyCss(string $css): string
    {
        // Remove CSS comments
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        // Replace multiple whitespace with a single space
        $css = preg_replace('/\s+/', ' ', $css);
        // Remove spaces around structural characters
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        return trim($css);
    }
}