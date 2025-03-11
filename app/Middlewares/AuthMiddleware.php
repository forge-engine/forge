<?php
declare(strict_types=1);

namespace App\Middlewares;

use Forge\Core\Contracts\MiddlewareInterface;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

#[Service]
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!$this->isAuthenticated($request)) {
            return new Response("Unauthorized", 401);
        }

        return $next($request);
    }

    private function isAuthenticated(Request $request): bool
    {
        return $request->hasHeader("Authorization");
    }
}
