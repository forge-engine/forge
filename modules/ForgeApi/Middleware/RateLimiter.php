<?php

namespace Forge\Modules\ForgeApi;

use Closure;
use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Http\Session;

class RateLimiter extends MiddlewareInterface
{

    private array $limits = [];
    private $defaultLimit = 100;

    public function __construct(int $defaultLimit = 100)
    {
        $this->defaultLimit = $defaultLimit;
    }

    /**
     * @param Closure(): void $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->ip();
        $this->limits[$key] = ($this->limits[$key] ?? 0) + 1;

        if ($this->limits[$key] > $this->defaultLimit) {
            return (new Response())
                ->setHeader('Retry-After', 60)
                ->json(['message' => 'Too many request'])
                ->setStatusCode(429);
        }

        return $next($request);
    }
}