<?php

namespace Forge\Modules\ForgeApi;

use Closure;
use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Http\Request;
use Forge\Http\Response;

class ApiAuth extends MiddlewareInterface
{

    /**
     * @param Closure(): void $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->authenticate($request)) {
            return (new Response())->json(['message' => 'Unauthorized'])->setStatusCode(401);
        }

        return $next($request);
    }

    private function authenticate(Request $request): bool
    {
        $token = $request->getHeader('Authorization');
        return (bool)preg_match('/Bearer\s+(.+)/', $token);
    }
}