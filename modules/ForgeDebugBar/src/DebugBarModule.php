<?php

namespace App\Modules\ForgeDebugBar;

use App\Modules\ForgeDebugbar\Collectors\MemoryCollector;
use App\Modules\ForgeDebugbar\Collectors\MessageCollector;
use App\Modules\ForgeDebugbar\Collectors\RequestCollector;
use App\Modules\ForgeDebugbar\Collectors\RouteCollector;
use App\Modules\ForgeDebugbar\Collectors\SessionCollector;
use App\Modules\ForgeDebugbar\Collectors\TimeCollector;
use Forge\Core\Collectors\DatabaseCollector;
use Forge\Core\Collectors\ExceptionCollector;
use Forge\Core\Collectors\TimelineCollector;
use Forge\Core\Collectors\ViewCollector;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\PostInstall;
use Forge\Core\Module\Attributes\PostUninstall;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\LifecycleHookName;
use Forge\Traits\InjectsAssets;

#[Service]
#[Module(
  name: 'ForgeDebugBar',
  version: '1.1.0',
  description: 'A debug bar by Forge',
  order: 3,
  author: 'Forge Team',
  license: 'MIT',
  type: 'generic',
  tags: ['generic', 'debug', 'debug-bar', 'debug-bar-system', 'debug-bar-library', 'debug-bar-framework']
)]
#[Provides(\App\Modules\ForgeDebugBar\DebugBar::class, version: '1.1.0')]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[ConfigDefaults(defaults: [
  'forge_debug_bar' => [
    'enabled' => true
  ]
])]
#[PostInstall(command: 'asset:link', args: ['--type=module', '--module=forge-debug-bar'])]
#[PostUninstall(command: 'asset:unlink', args: ['--type=module', '--module=forge-debug-bar'])]
class DebugBarModule
{
  use InjectsAssets;

  private static ?MemoryCollector $memoryCollector = null;

  public function register(Container $container): void
  {
    $container->bind(\App\Modules\ForgeDebugBar\DebugBar::class, \App\Modules\ForgeDebugBar\DebugBar::class);
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

    $debugbar->addCollector('time', function ($startTime) {
      return TimeCollector::collect($startTime);
    });

    try {
      $container = Container::getInstance();

      if ($container->has(TimelineCollector::class)) {
        /** @var TimelineCollector $timelineCollector */
        $timelineCollector = $container->get(TimelineCollector::class);
        $timelineData = $timelineCollector->collect($request);
        $debugbar->addCollector('timeline', function () use ($timelineData) {
          return $timelineData;
        });
      }

      if ($container->has(ViewCollector::class)) {
        /** @var ViewCollector $viewCollector */
        $viewCollector = $container->get(ViewCollector::class);
        $viewData = $viewCollector->collect($request);
        $debugbar->addCollector('views', function () use ($viewData) {
          return $viewData;
        });
      }

      if ($container->has(ExceptionCollector::class)) {
        /** @var ExceptionCollector $exceptionCollector */
        $exceptionCollector = $container->get(ExceptionCollector::class);
        $exceptionData = $exceptionCollector->collect($request);
        $debugbar->addCollector('exceptions', function () use ($exceptionData) {
          return $exceptionData;
        });
      }

      if ($container->has(DatabaseCollector::class)) {
        /** @var DatabaseCollector $databaseCollector */
        $databaseCollector = $container->get(DatabaseCollector::class);
        $databaseData = $databaseCollector->collect($request);
        $debugbar->addCollector('Database', function () use ($databaseData) {
          return $databaseData;
        });
      }
    } catch (\Throwable $e) {
    }

    $debugbar->addCollector('messages', function ($startTime) {
      return MessageCollector::collect($startTime);
    });

    $routeData = RouteCollector::collect();
    $debugbar->addCollector('route', function () use ($routeData) {
      return $routeData;
    });

    $this->registerDebugBarAssets();
    $this->injectAssets($response);
  }

  private function registerDebugBarAssets(): void
  {
    $debugbar = $this->getDebugbarInstance();
    $container = Container::getInstance();

    if (!$debugbar->shouldEnableDebugBar($container)) {
      return;
    }

    $cssLinkTag = '<link rel="stylesheet" href="/assets/modules/forge-debug-bar/css/debugbar.css">';
    $this->registerAsset(assetHtml: $cssLinkTag, beforeTag: '</head>');

    $debugBarHtml = $debugbar->render();

    $jsScriptTag = '<script src="/assets/modules/forge-debug-bar/js/debugbar.js"></script>';
    $this->registerAsset(assetHtml: $debugBarHtml . "\n" . $jsScriptTag, beforeTag: '</body>');
  }

  private function getDebugbarInstance(): \App\Modules\ForgeDebugBar\DebugBar
  {
    return \App\Modules\ForgeDebugBar\DebugBar::getInstance();
  }
}
