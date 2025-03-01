<?php

namespace MyApp\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Core\Helpers\Debug;
use Forge\Http\Request;
use Forge\Http\Response;
use Closure;

class RequestLoggingMiddleware extends MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $method = $request->getMethod();
        $uri = $request->getUri();
        Debug::message("Request received: Method=$method, URI=$uri");
        return $next($request);
    }
}