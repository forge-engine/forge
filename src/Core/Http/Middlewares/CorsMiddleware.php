<?php

namespace Forge\Core\Http\Middlewares;

use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Exceptions\InvalidMiddlewareResponse;

#[Service]
class CorsMiddleware extends Middleware
{
    /**
     * @throws InvalidMiddlewareResponse
     */
    public function handle(Request $request, callable $next): Response
    {
        $origin = $request->getHeader("Origin");
        $response = $next($request);

        if (!$response instanceof Response) {
            throw new InvalidMiddlewareResponse();
        }
        if ($origin === null) {
            $response->setHeader("Access-Control-Allow-Origin", "*");
        } else {
            $response->setHeader("Access-Control-Allow-Origin", $origin);
        }
        $response->setHeader(
            "Access-Control-Allow-Methods",
            "GET, POST, PUT, DELETE, OPTIONS"
        );
        $response->setHeader(
            "Access-Control-Allow-Headers",
            "Content-Type, Authorization"
        );
        $response->setHeader("Access-Control-Allow-Credentials", "true");
        $response->setHeader("Access-Control-Max-Age", "86400");
        return $response;
    }
}
