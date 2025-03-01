<?php

namespace Forge\Core\Bootstrap;

use Forge\Core\DependencyInjection\Container;
use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Core\Contracts\Modules\AppInterface;

class AppManager
{
    private static ?AppManager $instance = null;
    private array $apps = [];
    private array $lifecycleHooks = [

    ];

    public function __construct()
    {
        $this->lifecycleHooks = array_fill_keys([
            'beforeBoot',
            'afterBoot',
            'afterConfigLoaded',
            'beforeAppRegister',
            'afterAppRegister',
            'beforeAppBoot',
            'afterAppBoot',
            'beforeRequest',
            'afterRequest',
            'beforeShutdown',
            'afterResponse',
            'beforeResponse',
            'beforeModuleLoad',
            'afterModuleLoad',
            'beforeModuleRegister',
            'afterModuleRegister'
        ], []);
    }

    public static function init(): AppManager
    {
        if (self::$instance === null) {
            self::$instance = new AppManager();
        }
        return self::$instance;
    }

    public static function getAppManager(): AppManager
    {
        if (self::$instance === null) {
            throw new \RuntimeException("AppManager not initialized. Call AppManager::init() first.");
        }
        return self::$instance;
    }

    public function register(AppInterface $app): void
    {
        $this->apps[] = $app;
    }

    public function getApps(): array
    {
        return $this->apps;
    }

    public function trigger(string $event, Container $container, ?Request $request = null, ?Response $response = null): void
    {
        foreach ($this->lifecycleHooks[$event] as $callback) {
            $callback($container, $request, $response);
        }
    }

    /**
     * @param callable(): mixed $callback
     */
    public function addHook(string $event, callable $callback): void
    {
        if (!isset($this->lifecycleHooks[$event])) {
            throw new \InvalidArgumentException("Invalid lifecycle event: {$event}");
        }
        $this->lifecycleHooks[$event][] = $callback;
    }

    public function bootApps(Container $container): void
    {
        $this->trigger('beforeBoot', $container);
        foreach ($this->apps as $app) {
            if (method_exists($app, 'onBeforeBoot')) {
                $this->addHook('beforeAppBoot', [$app, 'onBeforeBoot']);
            }
            if (method_exists($app, 'onAfterConfigLoaded')) {
                $this->addHook('afterConfigLoaded', [$app, 'onAfterConfigLoaded']);
            }
            if (method_exists($app, 'onBeforeAppRegister')) {
                $this->addHook('beforeAppRegister', [$app, 'onBeforeAppRegister']);
            }
            if (method_exists($app, 'onAfterBoot')) {
                $this->addHook('afterAppBoot', [$app, 'onAfterBoot']);
            }
            if (method_exists($app, 'onAfterAppRegister')) {
                $this->addHook('afterAppRegister', [$app, 'onAfterAppRegister']);
            }
            $this->trigger('afterConfigLoaded', $container);
            $this->trigger('beforeAppRegister', $container);
            $app->register($container);
            $this->trigger('afterAppRegister', $container);
            $this->trigger('beforeAppBoot', $container);
            $app->boot($container);
            $this->trigger('afterAppBoot', $container);
        }
    }

}
