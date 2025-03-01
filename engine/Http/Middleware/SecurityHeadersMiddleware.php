<?php

namespace Forge\Http\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Http\Request;
use Forge\Http\Response;
use Closure;
use Exception;

class SecurityHeadersMiddleware extends MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$response instanceof Response) {
            throw new Exception('Middleware did not return a Response object.');
        }
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('Content-Security-Policy', "default-src 'self'");

        return $response;
    }
}
