<?php

namespace Forge\Modules\ForgeRouter\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\App;
use Forge\Core\Traits\OutputHelper;

class RouteListCommand implements CommandInterface
{
    use OutputHelper;

    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getName(): string
    {
        return 'route:list';
    }

    public function getDescription(): string
    {
        return 'List all registered routes';
    }

    public function execute(array $args): int
    {
        $router = App::router();
        $routes = $router->getRoutes();

        foreach ($routes as $route) {
            $this->info("{$route['method']} - {$route['uri']}");
        }

        return 0;
    }
}
