<?php

namespace Forge\Modules\ForgeDebugbar;

use Forge\Core\Contracts\Modules\DebugBarInterface;
use Forge\Core\Configuration\Config;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\Debug;
use Forge\Core\Helpers\Path;
use Forge\Http\Response;


class DebugBar implements DebugBarInterface
{
    private array $collectors = [];
    private float $startTime;
    private int $startMemory;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();

    }

    public function addCollector(string $name, callable $collector): void
    {

        $this->collectors[$name] = $collector;
    }


    public function getData(): array
    {
        $data = [];


        foreach ($this->collectors as $name => $collectorCallable) {
            $collectorData = call_user_func($collectorCallable, $this->startTime);
            $data[$name] = $collectorData;
        }

        $data['memory'] = $this->getMemoryUsage();
        $data['php_version'] = phpversion();

        return $data;
    }

    private function getMemoryUsage(): string
    {
        $memoryUsageBytes = memory_get_usage() - $this->startMemory;
        $memoryUsageMB = round($memoryUsageBytes / (1024 * 1024), 2);
        return $memoryUsageMB . 'MB';
    }

    public function render(): string
    {
        $modulePath = Path::modulePath('ForgeDebugBar', 'views/debugbar.php');
        ob_start();
        extract(['data' => $this->getData()]);
        include $modulePath;
        return ob_get_clean();
    }

    public function injectDebugBarIfEnabled(Response $response, Container $container): Response
    {
        if ($this->shouldEnableDebugBar($container)) {
            $contentTypeHeader = $response->getHeader('Content-Type');
            if ($contentTypeHeader !== null && strpos($contentTypeHeader, 'text/html') !== false) {
                $content = $response->getContent();
                $debugBarHtml = $this->render();
                $content = $this->injectDebugBarIntoHtml($content, $debugBarHtml, $container);
                $response->setContent($content);
            }
        }
        return $response;
    }

    public function injectDebugBarIntoHtml(string $htmlContent, string $debugBarHtml, Container $container): string
    {
        $cssLinkTag = sprintf('<link rel="stylesheet" href="/modules/debug-bar/css/debugbar.css">');
        $jsScriptTag = sprintf('<script src="/modules/debug-bar/js/debugbar.js"></script>');

        if (!is_string($htmlContent)) {
            return $debugBarHtml;
        }

        $injectionPoint = strripos($htmlContent, '</body>');

        if ($injectionPoint !== false) {
            $injectedContent = substr($htmlContent, 0, $injectionPoint) .
                $cssLinkTag . "\n" .
                $debugBarHtml . "\n" .
                $jsScriptTag . "\n" .
                substr($htmlContent, $injectionPoint);
            return $injectedContent;
        } else {
            return $htmlContent . "\n" . $cssLinkTag . "\n" . $debugBarHtml . "\n" . $jsScriptTag;
        }
    }

    public function shouldEnableDebugBar(Container $container): bool
    {
        $forgeDebug = filter_var($_ENV['FORGE_APP_DEBUG'] === 'true' ?? false);
        /** @var Config $config */
        $config = $container->get(Config::class);
        $configEnabled = $config->get('debugbar.enabled', true);
        return $configEnabled && $forgeDebug;
    }
}