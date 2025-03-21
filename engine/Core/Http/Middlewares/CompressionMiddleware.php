<?php
declare(strict_types=1);

namespace Forge\Core\Http\Middlewares;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Exceptions\InvalidMiddlewareResponse;

#[Service]
class CompressionMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        if (!$response instanceof Response) {
            throw new InvalidMiddlewareResponse();
        }

        $acceptEncoding = $request->getHeader('Accept-Encoding');

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