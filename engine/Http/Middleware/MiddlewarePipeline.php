<?php

namespace Forge\Http\Middleware;

use Forge\Http\Response;
use Forge\Http\Request;
use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Closure;

class MiddlewarePipeline
{
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function run(Request $request, Closure $coreHandler): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            fn(Closure $next, MiddlewareInterface $middleware) => fn(Request $request) => $middleware->handle($request, $next),
            $coreHandler
        );

        return $pipeline($request);
    }
}
