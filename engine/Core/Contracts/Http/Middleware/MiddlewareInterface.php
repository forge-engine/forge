<?php

namespace Forge\Core\Contracts\Http\Middleware;

use Forge\Core\DependencyInjection\Container;
use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Http\Session;
use Closure;

abstract class MiddlewareInterface
{
    protected Container $container;
    protected array $config;
    protected Session $session;

    /**
     * @param array<int,mixed> $config
     */
    public function __construct(Container $container, Session $session, array $config = [])
    {
        $this->container = $container;
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * @param Closure(): void $next
     */
    abstract public function handle(Request $request, Closure $next): Response;
}
