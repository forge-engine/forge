<?php
declare(strict_types=1);

namespace Forge\Core\Routing;

use Forge\Core\Contracts\MiddlewareInterface;
use Forge\Core\DI\Container;
use Forge\Core\Http\Attributes\Middleware;
use ReflectionClass;
use Forge\Core\Http\Request;

final class Router
{
    /** @var array<string, array{controller: class-string, method: string}> */
    private array $routes = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @throws \ReflectionException
     */
    public function registerControllers(string $controllerClass): void
    {
        $reflection = new ReflectionClass($controllerClass);

        foreach ($reflection->getMethods() as $method) {
            $routeAttributes = $method->getAttributes(Route::class);
            $middlewareAttributes = array_merge(
                $reflection->getAttributes(Middleware::class),
                $method->getAttributes(Middleware::class)
            );

            foreach ($routeAttributes as $attr) {
                $route = $attr->newInstance();
                $middleware = array_map(
                    fn($attr) => $attr->newInstance()->middlewareClass,
                    $middlewareAttributes
                );

                $params = [];
                $pattern = preg_replace_callback(
                    "/\{([a-zA-Z0-9_]+)\}/",
                    function ($matches) use (&$params) {
                        $params[] = $matches[1];
                        return "([a-zA-Z0-9_]+)";
                    },
                    $route->path
                );

                $regex = "#^{$pattern}/?$#";

                $this->routes[$route->method . $regex] = [
                    "controller" => $controllerClass,
                    "method" => $method->getName(),
                    "params" => $params,
                    "middleware" => $middleware,
                ];
            }
        }
    }

    public function dispatch(Request $request): mixed
    {
        $uri = $request->serverParams["REQUEST_URI"];
        $method = $request->getMethod();
        $path = parse_url($uri, PHP_URL_PATH);
        $routeKey = $method . "#^{$path}/?$#";

        $routeFound = false;
        foreach ($this->routes as $routeRegex => $routeInfo) {
            if (strpos($routeRegex, $method) === 0) {
                $regex = substr($routeRegex, strlen($method));
                if (preg_match($regex, $path, $matches)) {
                    $routeInfo["regex_matches"] = $matches;
                    $route = $routeInfo;
                    $routeFound = true;
                    break;
                }
            }
        }

        if (!$routeFound) {
            throw new \RuntimeException("Route not found: $method $path");
        }

        $pipeline = array_reduce(
            array_reverse($route["middleware"] ?? []),
            fn($next, $middlewareClass) => function ($req) use (
                $middlewareClass,
                $next
            ) {
                $middlewareInstance = $this->container->make($middlewareClass);
                if (!($middlewareInstance instanceof MiddlewareInterface)) {
                    throw new \RuntimeException(
                        "Middleware class '$middlewareClass' must implement MiddlewareInterface."
                    );
                }
                return $middlewareInstance->handle($req, $next);
            },
            function ($request) use ($route) {
                return $this->runController($route, $request);
            }
        );

        return $pipeline($request);
    }

    private function runController(array $route, Request $request): mixed
    {
        $controllerClass = $route["controller"];
        $methodName = $route["method"];
        $params = [];

        if (isset($route["params"], $route["regex_matches"])) {
            array_shift($route["regex_matches"]);
            foreach ($route["params"] as $index => $paramName) {
                $params[$paramName] = $route["regex_matches"][$index];
            }
        }

        $controllerInstance = $this->container->make($controllerClass);
        return $controllerInstance->$methodName($request, $params);
    }
}
