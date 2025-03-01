<?php

namespace MyApp;

use Forge\Core\Contracts\Modules\RouterInterface;
use Forge\Core\Contracts\Modules\AppInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\App;
use Forge\Core\Helpers\Path;
use Forge\Http\Request;
use Forge\Http\Response;

class MyApp extends AppInterface
{
    private static ?MyApp $instance = null;
    private Container $container;

    public static function init(): MyApp
    {
        if (self::$instance === null) {
            self::$instance = new MyApp();
        }
        return self::$instance;
    }

    public static function getMyApp(): MyApp
    {
        if (self::$instance === null) {
            throw new \RuntimeException("MyApp not initialized. Call MyApp::init() first.");
        }
        return self::$instance;
    }

    public function register(Container $container): void
    {
        $this->container = $container;
    }

    public function onAfterAppRegister(Container $container): void
    {
        $config = App::config();
        $webRouteFile = $config->get('routes.web');
        $apiRouteFile = $config->get('routes.api');

        $webRoutesPath = Path::basePath("/$webRouteFile");
        $apiRoutesPath = Path::basePath("/$apiRouteFile");

        if (file_exists($webRoutesPath)) {
            require_once $webRoutesPath;
        }
        if (file_exists($apiRoutesPath)) {
            require_once $apiRoutesPath;
        }
    }

    public function boot(Container $container): void
    {
    }

    public function handleRequest(Request $request): Response
    {
        $router = $this->container->get(RouterInterface::class);
        return $router->handleRequest($request);
    }

    public function handleCommand(array $args): int
    {
        return 0;
    }
}
