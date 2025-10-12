<?php

namespace App\Modules\ForgeDebugbar;

use App\Modules\ForgeDebugbar\Collectors\ExceptionCollector;
use App\Modules\ForgeDebugbar\Collectors\MemoryCollector;
use App\Modules\ForgeDebugbar\Collectors\MessageCollector;
use App\Modules\ForgeDebugbar\Collectors\RequestCollector;
use App\Modules\ForgeDebugbar\Collectors\RouteCollector;
use App\Modules\ForgeDebugbar\Collectors\SessionCollector;
use App\Modules\ForgeDebugbar\Collectors\TimeCollector;
use App\Modules\ForgeDebugbar\Collectors\TimelineCollector;
use App\Modules\ForgeDebugbar\Collectors\ViewCollector;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\LifecycleHookName;

#[Service]
#[Module(name: 'ForgeDebugBar', description: 'Forge Debugbar', order: 1, version: '0.1.1')]
#[Provides(DebugBar::class, version: '0.1.1')]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[ConfigDefaults(defaults: [
    'forge_debug_bar' => [
        'enabled' => true
    ]
])]
class DebugBarModule
{
    private static ?MemoryCollector $memoryCollector = null;

    public function register(Container $container): void
    {
        $container->bind(DebugBar::class, DebugBar::class);
    }

    #[LifecycleHook(hook: LifecycleHookName::BEFORE_REQUEST)]
    public function onBeforeRequest(Request $request): void
    {
        add_timeline_event('onBeforeRequest', 'start');
    }

    #[LifecycleHook(hook: LifecycleHookName::AFTER_REQUEST)]
    public function onAfterRequest(Request $request, Response $response): void
    {
        add_timeline_event('onAfterRequest', 'end');
        if (!$response->hasHeader('Content-Type')) {
            $response->setHeader('Content-Type', 'text/html; charset=UTF-8');
        }

        self::$memoryCollector = MemoryCollector::instance();

        $debugbar = $this->getDebugbarInstance();

        $requestData = RequestCollector::collect($request);
        $debugbar->addCollector('request', function () use ($requestData) {
            return $requestData;
        });

        $sessionData = SessionCollector::collect();
        $debugbar->addCollector('session', function () use ($sessionData) {
            return $sessionData;
        });

        $debugbar->addCollector('memory', function () {
            return self::$memoryCollector ? self::$memoryCollector->getMemoryUsage() : ['error' => 'Memory collector not initialized'];
        });

        $timeData = TimeCollector::collect();
        $debugbar->addCollector('time', function () use ($timeData) {
            return $timeData;
        });

        $timelineData = TimelineCollector::collect();
        $debugbar->addCollector('timeline', function () use ($timelineData) {
            return $timelineData;
        });

        $messagesData = MessageCollector::collect();
        $debugbar->addCollector('messages', function () use ($messagesData) {
            return $messagesData;
        });

        $routeData = RouteCollector::collect();
        $debugbar->addCollector('route', function () use ($routeData) {
            return $routeData;
        });

        $vieDataData = ViewCollector::collect();
        $debugbar->addCollector('views', function () use ($vieDataData) {
            return $vieDataData;
        });

        $exceptionData = ExceptionCollector::collect();
        $debugbar->addCollector('exceptions', function () use ($exceptionData) {
            return $exceptionData;
        });

        DebugBar::getInstance()->injectDebugBarIfEnabled($response, Container::getInstance());
    }

    private function getDebugbarInstance(): DebugBar
    {
        return DebugBar::getInstance();
    }
}
