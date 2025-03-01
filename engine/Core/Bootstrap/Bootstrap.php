<?php

namespace Forge\Core\Bootstrap;

use Forge\Core\Bootstrap\AppManager;
use Forge\Core\Configuration\Config;
use Forge\Core\Contracts\Events\EventDispatcherInterface;
use Forge\Core\Contracts\Modules\DebugBarInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\Debug;
use Forge\Core\Routing\CoreRouter;
use Forge\Http\Middleware\MiddlewarePipeline;
use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Http\Session;
use Forge\Core\Contracts\Modules\ErrorHandlerInterface;
use Forge\Core\Bootstrap\Setup\AppManagerSetup;
use Forge\Core\Bootstrap\Setup\AppSetup;
use Forge\Core\Bootstrap\Setup\EnvironmentSetup;
use Forge\Core\Bootstrap\Setup\ErrorHandlingSetup;
use Forge\Core\Bootstrap\Setup\MiddlewareSetup;
use Forge\Core\Bootstrap\Setup\ModuleSetup;
use Forge\Core\Bootstrap\Setup\ServiceRegistry;
use Forge\Core\Events\RequestReadyForDebugBarCollector;
use Forge\Core\Events\ResponseReadyForDebugBarInjection;

class Bootstrap
{
    private static ?Bootstrap $instance = null;
    private array $modules = [];
    private array $apps = [];
    private Container $container;
    private AppManager $appManager;
    private string $basePath;
    private MiddlewarePipeline $middlewareStack;
    private bool $isCli;

    public function __construct(string $basePath, bool $isCli = false)
    {
        $this->basePath = $basePath;
        Container::init();
        $this->container = Container::getContainer();
        $this->isCli = $isCli;
        EnvironmentSetup::setup($this->container, $this->basePath);
        ServiceRegistry::registerServices($this->container);
        ErrorHandlingSetup::setupErrorHandling($this->container, $this->isCli, $this);
        $this->appManager = AppManagerSetup::setup($this->container);
        ModuleSetup::setup($this->container, $this->appManager, $this->modules);

        if ($this->container->has(\Forge\Core\Contracts\Modules\RouterInterface::class)) {
            AppSetup::setup($this->container, $this->appManager, $this->apps);
        }

        $this->middlewareStack = MiddlewareSetup::setup($this->container, $this->isCli, $this);
        $this->appManager->trigger('afterBoot', $this->container);
    }

    public static function init(string $basePath, bool $isCli = false): Bootstrap
    {
        if (self::$instance === null) {
            self::$instance = new Bootstrap($basePath, $isCli);
        }
        return self::$instance;
    }

    public static function getBootstrap(): Bootstrap
    {
        if (self::$instance === null) {
            throw new \RuntimeException("Bootstrap not initialized. Call Bootstrap::init() first.");
        }
        return self::$instance;
    }

    public function handleRequest(Request $request): Response
    {
        $coreHandler = $this->createCoreHandler();
        $response = $this->middlewareStack->run($request, $coreHandler);

        if (!$this->isCli) {
            if ($this->container->has(DebugBarInterface::class)) {
                /** @var EventDispatcherInterface $eventDispatcher */
                $eventDispatcher = $this->container->get(EventDispatcherInterface::class);
                $eventDispatcher->dispatch(new ResponseReadyForDebugBarInjection($response, $this->container));
            }
        }

        return $response;
    }

    function registerMiddlewares(MiddlewarePipeline $pipeline): void
    {
        $config = $this->container->get(Config::class);
        $middlewares = $config->get('app.middleware', []);
        $session = $this->container->get(Session::class);

        foreach ($middlewares as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                $middlewareConfigKey = str_replace('\\', '.', strtolower(ltrim($middlewareClass, '\\')));
                $middlewareConfig = $config->get($middlewareConfigKey, []);

                $pipeline->add(new $middlewareClass($this->container, $session, $middlewareConfig));
            } else {
                error_log("Middleware class {$middlewareClass} not found.");
            }
        }
    }

    private function createCoreHandler(): callable
    {
        return function (Request $request) {
            if (!$this->container->has(\Forge\Core\Contracts\Modules\RouterInterface::class)) {
                $router = new CoreRouter();
                $router->handleRequest($request);
                exit;
            } else {
                if (!$this->isCli) {
                    if ($this->container->has(DebugBarInterface::class)) {
                        /** @var EventDispatcherInterface $eventDispatcher */
                        $eventDispatcher = $this->container->get(EventDispatcherInterface::class);
                        $eventDispatcher->dispatch(new RequestReadyForDebugBarCollector($request, $this->container));
                    }
                }

                foreach ($this->appManager->getApps() as $app) {
                    /** @var Response $response */
                    $response = $app->handleRequest($request);
                    if ($response !== null) {
                        return $response;
                    }
                }
                throw new \RuntimeException("No app handled the request");
            }
        };
    }

    public function handleException(\Throwable $e): void
    {
        Debug::exceptionCollector($e);
        $request = $this->container->get(Request::class);
        $errorHandler = $this->container->get(ErrorHandlerInterface::class);
        $response = $errorHandler->handle($e, $request);
        $response->send();
    }

    public function handleError(int $errno, string $errstr, ?string $errfile = null, ?int $errline = null): void
    {
        $exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        Debug::exceptionCollector($exception);
        $request = $this->container->get(Request::class);
        $errorHandler = $this->container->get(ErrorHandlerInterface::class);
        $response = $errorHandler->handle($exception, $request);
        $response->send();
        exit(1);
    }

    /**
     * Get the application's dependency injection container.
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get the application's manager instance.
     *
     * @return AppManager
     */
    public function getAppManager(): AppManager
    {
        return $this->appManager;
    }

    /**
     * Get the base path of the application.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get the registered modules.
     *
     * @return array<string, mixed>
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Get the registered apps.
     *
     * @return array<string, mixed>
     */
    public function getApps(): array
    {
        return $this->apps;
    }

    /**
     * Get the application's middleware pipeline.
     *
     * @return MiddlewarePipeline
     */
    public function getMiddlewarePipeline(): MiddlewarePipeline
    {
        return $this->middlewareStack;
    }
}
