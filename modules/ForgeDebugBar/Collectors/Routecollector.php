<?php

namespace Forge\Modules\ForgeDebugbar\Collectors;

use Forge\Core\Contracts\Modules\RouterInterface;
use Forge\Core\DependencyInjection\Container;

class RouteCollector implements CollectorInterface
{
    private array $routesData = [];

    public static function collect(...$args): array
    {
        return self::instance()->collectRoutes();
    }

    public static function instance(): self
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }
        return $instance;
    }

    public function collectRoutes(): array
    {
        $container = Container::getContainer();
        if ($container->has(RouterInterface::class)) {
            /** @var RouterInterface $router */
            $router = $container->get(RouterInterface::class);
            $currentRoute = $router->getCurrentRoute();


            if ($currentRoute) {
                return [
                    'uri' => $currentRoute['uri'] ?? 'N/A',
                    'method' => $currentRoute['method'] ?? 'N/A',
                    'handler' => $this->formatHandler($currentRoute['handler'] ?? 'N/A'),
                    'middleware' => $currentRoute['middleware'] ?? [],
                ];
            } else {
                return ['message' => 'No current route matched.'];
            }
        } else {
            return [['error' => 'RouterInterface not bound in Container']];
        }
    }

    private function formatHandler(array|callable $handler): string
    {
        if (is_callable($handler)) {
            if (is_array($handler)) {
                if (is_string($handler[0])) {
                    return $handler[0] . '::' . $handler[1];
                } else {
                    return 'Closure in ' . get_class($handler[0]) . '->' . $handler[1];
                }
            } else {
                $reflection = new \ReflectionFunction($handler);
                return 'Closure in ' . $reflection->getFileName() . ':' . $reflection->getStartLine();
            }
        } elseif (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
            return $handler[0] . '::' . $handler[1];
        } else {
            return 'Unknown Handler Type';
        }
    }
}