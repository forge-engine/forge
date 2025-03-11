<?php
declare(strict_types=1);

namespace Forge\Core\Routing;

use Forge\Core\DI\Container;
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

            foreach ($routeAttributes as $attr) {
                $route = $attr->newInstance();

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
                ];
            }
        }
    }

    public function dispatch(Request $request): mixed
    {
        $uri = $request->serverParams["REQUEST_URI"];
        $method = $request->serverParams["REQUEST_METHOD"];

        $path = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $routeRegex => $routeInfo) {
            if (strpos($routeRegex, $method) === 0) {
                $regex = substr($routeRegex, strlen($method));
                if (preg_match($regex, $path, $matches)) {
                    array_shift($matches);

                    $params = [];
                    foreach ($routeInfo["params"] as $index => $paramName) {
                        $params[$paramName] = $matches[$index];
                    }

                    $controllerClass = $routeInfo["controller"];
                    $methodName = $routeInfo["method"];

                    $controllerInstance = $this->container->make(
                        $controllerClass
                    );

                    return $controllerInstance->$methodName($request, $params);
                }
            }
        }

        throw new \RuntimeException("Route not found: $method $path");
    }
}
