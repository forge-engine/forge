<?php

namespace MyApp\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Core\Helpers\Debug;
use Forge\Http\Request;
use Forge\Http\Response;
use Closure;

class AdminAuthMiddleware extends MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $isAdmin = false;

        if (!$isAdmin) {
            $response = new Response();
            $response->setStatusCode(403);
            Debug::message('[AdminAuthMiddleware] Not Authorized');
            return $response->html('You do not have permission to access this area.');
        }

        return $next($request);
    }
}