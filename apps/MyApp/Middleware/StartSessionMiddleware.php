<?php

namespace MyApp\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Http\Request;
use Forge\Http\Response;
use Closure;

class StartSessionMiddleware extends MiddlewareInterface
{


    /**
     * Process the incoming request.
     *
     * @param Request $request
     * @param callable $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->session->start();
        return $next($request);
    }
}