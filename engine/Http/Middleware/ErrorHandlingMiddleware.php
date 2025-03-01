<?php

namespace Forge\Http\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Core\Contracts\Modules\ErrorHandlerInterface;
use Closure;
use Throwable;

class ErrorHandlingMiddleware extends MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            $handler = $this->container->get(ErrorHandlerInterface::class);
            return $handler->handle($e, $request);
        }
    }
}
