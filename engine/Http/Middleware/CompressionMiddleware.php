<?php

namespace Forge\Http\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Http\Request;
use Forge\Http\Response;
use Closure;
use Exception;

class CompressionMiddleware extends MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$response instanceof Response) {
            throw new Exception('Middleware did not return a Response object.');
        }

        $acceptEncoding = $request->getHeaderLine('Accept-Encoding');

        if (isset($response->getHeaders()['Content-Encoding'])) {
            return $response;
        }

        $content = $response->getContent();
        if (empty($content)) {
            return $response;
        }
        if (!empty($acceptEncoding)) {
            if (str_contains($acceptEncoding, 'gzip')) {
                $compressedContent = gzencode($content, 9);
                if ($compressedContent !== false) {
                    $response->setHeader('Content-Encoding', 'gzip');
                    $response->setContent($compressedContent);
                }
            } elseif (str_contains($acceptEncoding, 'deflate')) {
                $compressedContent = gzdeflate($content, 9);
                if ($compressedContent !== false) {
                    $response->setHeader('Content-Encoding', 'deflate');
                    $response->setContent($compressedContent);
                }
            }
        }

        return $response;
    }
}
