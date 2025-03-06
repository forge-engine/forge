<?php

namespace MyApp\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Http\Request;
use Forge\Http\Response;
use Closure;

class CacheContentMiddleware extends MiddlewareInterface
{
    private string $cachePath;
    private int $cacheDuration = 3600; // 1 hour in seconds

    public function __construct()
    {
        // Set cache directory for HTML content
        $this->cachePath = BASE_PATH . '/cache/html';
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Generate cache key from request URI
        $cacheKey = md5($request->getUri());
        $cacheFile = $this->cachePath . '/' . $cacheKey . '.html';

        // Serve cached content if it exists and isn’t expired
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheDuration) {
            $cachedHtml = file_get_contents($cacheFile);
            return (new Response())->setContent($cachedHtml)->setHeader('Content-Type', 'text/html');
        }

        // Process the request and get the response
        $response = $next($request);

        $minifier = new MinifyMiddleware();
        $response = $minifier->handle($request, fn() => $response);

        // Cache HTML responses
        if ($response->getHeader('Content-Type') === 'text/html') {
            $html = $response->getContent();
            file_put_contents($cacheFile, $html);
        }

        return $response;
    }
}