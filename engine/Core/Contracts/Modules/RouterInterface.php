<?php

namespace Forge\Core\Contracts\Modules;

use Forge\Http\Request;
use Forge\Http\Response;

interface RouterInterface
{
    /**
     * @param callable(): mixed $handler
     */
    public function addRoute(string $method, string $uri, callable $handler): void;

    public function handleRequest(Request $request): Response;

    /**
     * @return void
     */
    public function getRoutes(): array;

    public function resource(string $uri, string $controller): void;

    public function group(string $prefix, callable $callback): void;

    public function get(string $uri, array|callable $handler, array $middleware = []): void;

    public function post(string $uri, array|callable $handler, array $middleware = []): void;

    public function put(string $uri, array|callable $handler, array $middleware = []): void;

    public function patch(string $uri, array|callable $handler, array $middleware = []): void;

    public function delete(string $uri, array|callable $handler, array $middleware = []): void;

    public function middleware(array $middleware): void;

    public function getCurrentRoute(): ?array;

    public function reservePath(string $uri): void;
}
