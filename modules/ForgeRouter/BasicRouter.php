<?php

namespace Forge\Modules\ForgeRouter;

use Forge\Core\Contracts\Modules\RouterInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Http\Middleware\MiddlewarePipeline;
use Forge\Http\Request;
use Forge\Http\Response;

class BasicRouter implements RouterInterface
{
    private array $routes = [];
    private string $currentGroupPrefix = '';
    private array $currentGroupMiddleware = [];
    private ?array $currentRoute = null;
    private array $reservedPaths = [
        '/_debug',
        '/_health',
        '/_forge/info',
        '/spark'
    ];

    public function __construct(
        private Container $container
    )
    {

    }

    public function addRoute(string $method, string $uri, array|callable $handler, array $middleware = []): void
    {
        $prefix = $this->currentGroupPrefix;
        $prefixedUri = $uri;

        if ($prefix && $uri !== '/') {
            $prefixedUri = rtrim($prefix, '/') . '/' . ltrim($uri, '/');
        } elseif ($prefix && $uri === '/') {
            $prefixedUri = rtrim($prefix, '/');
        }

        if ($this->isReservedPath($prefixedUri)) {
            throw new \InvalidArgumentException("Route '{$prefixedUri}' is reserved and cannot be overridden.");
        }

        $routeMiddleware = array_merge($this->currentGroupMiddleware, $middleware);

        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $prefixedUri,
            'handler' => $handler,
            'middleware' => $routeMiddleware
        ];
    }

    private function isReservedPath(string $uri): bool
    {
        return in_array($uri, $this->reservedPaths, true);
    }

    public function reservePath(string $uri): void
    {
        if (!in_array($uri, $this->reservedPaths, true)) {
            $this->reservedPaths[] = $uri;
        }
    }

    public function handleRequest(Request $request): Response
    {
        $this->currentRoute = null;
        foreach ($this->routes as $route) {
            if ($this->matchesRoute($route, $request)) {
                $this->currentRoute = $route;
                return $this->resolveMiddlewarePipeline($route, $request);
            }
        }
        return (new Response())->setContent('Not found')->setStatusCode(404);
    }

    private function resolveMiddlewarePipeline(array $route, Request $request): Response
    {
        $handler = $route['handler'];
        $middlewareClasses = $route['middleware'];

        $middlewarePipeline = new MiddlewarePipeline();

        foreach ($middlewareClasses as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                $middlewarePipeline->add($this->container->make($middlewareClass));
            } else {
                error_log("Route middleware class {$middlewareClass} not found");
            }
        }

        $coreHandler = function (Request $request) use ($handler) {
            return $this->resolveHandler($handler, $request);
        };
        return $middlewarePipeline->run($request, $coreHandler);

    }

    /**
     * @param array<int,mixed> $route
     */
    private function matchesRoute(array $route, Request $request): bool
    {
        $requestUri = $request->getUri();
        $routeUri = $route['uri'];

        $normalizedRequestUri = $requestUri;

        if ($normalizedRequestUri !== '/' && substr($normalizedRequestUri, -1) === '/') {
            $normalizedRequestUri = rtrim($normalizedRequestUri, '/');
        }

        // Convert route URI to a regex pattern to capture parameters
        $routePattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $routeUri);
        $routePattern = '@^' . $routePattern . '$@';

        if (preg_match($routePattern, $normalizedRequestUri, $matches)) {
            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $request->setAttribute($key, $value);
                }
            }
            return $route['method'] === $request->getMethod();
        }

        return false;
    }

    private function resolveHandler(array|callable $handler, Request $request): Response
    {
        if (is_callable($handler)) {
            $reflection = new \ReflectionFunction($handler);
            $numParams = $reflection->getNumberOfRequiredParameters();

            if ($numParams === 2) {
                return $handler($request, $this->container);
            } else {
                return $handler($request);
            }
        }

        if (is_array($handler) && count($handler) === 2) {
            $class = $handler[0];
            $method = $handler[1];
            $controller = $this->container->make($class);
            return $controller->{$method}($request);
        }

        throw new \RuntimeException("Invalid route handler");
    }

    public function get(string $uri, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $uri, $handler, $middleware);
    }

    public function post(string $uri, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $uri, $handler, $middleware);
    }

    public function put(string $uri, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $uri, $handler, $middleware);
    }

    public function patch(string $uri, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $uri, $handler, $middleware);
    }

    public function delete(string $uri, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $uri, $handler, $middleware);
    }

    public function middleware(array $middleware): void
    {
        $this->currentGroupMiddleware = array_merge($this->currentGroupMiddleware, $middleware);
    }

    public function resource(string $uri, string $controller): void
    {
        $this->addRoute('GET', $uri, [$controller, 'index']);
        $this->addRoute('GET', $uri . '/create', [$controller, 'create']);
        $this->addRoute('POST', $uri, [$controller, 'store']);
        $this->addRoute('GET', $uri . '/{id}', [$controller, 'show']);
        $this->addRoute('GET', $uri . '/{id}/edit', [$controller, 'edit']);
        $this->addRoute('PUT', $uri . '/{id}', [$controller, 'update']);
        $this->addRoute('DELETE', $uri . '/{id}', [$controller, 'destroy']);
    }

    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousPrefix = $this->currentGroupPrefix;
        $previousMiddleware = $this->currentGroupMiddleware;
        $this->currentGroupPrefix .= $prefix;
        $this->currentGroupMiddleware = $middleware;

        $callback($this);
        $this->currentGroupPrefix = $previousPrefix;
        $this->currentGroupMiddleware = $previousMiddleware;
    }

    public function getCurrentRoute(): ?array
    {
        return $this->currentRoute;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
