<?php

namespace Forge\Modules\ForgeErrorHandler;

use Forge\Core\Contracts\Modules\ErrorHandlerInterface;
use Forge\Core\Configuration\Config;
use Forge\Core\DependencyInjection\Container;
use Forge\Http\Request;
use Forge\Http\Response;
use Throwable;

class ErrorModule implements ErrorHandlerInterface
{
    private ?Config $config;
    private ErrorPageRenderer $renderer;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->renderer = new ErrorPageRenderer(BASE_PATH);
    }

    public function register(Container $container): void
    {
    }

    public function onAfterConfigLoaded(Container $container): void
    {
        $this->config = $container->get(Config::class);
        $this->renderer = new ErrorPageRenderer(
            BASE_PATH
        );
    }

    public function handle(Throwable $e, Request $request): Response
    {
        if (!$this->shouldShowErrors()) {
            return new Response(500, 'Internal Server Error');
        }

        if (!$request) {
            error_log((string)$e);
            echo "\nError in console command:\n";
            echo "Message: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . "\n";
            echo "Line: " . $e->getLine() . "\n";
            exit(1);
        }
        $response = new Response();
        $response->setStatusCode(500);
        $response->html($this->renderer->render($e, $request));

        return $response;
    }

    private function shouldShowErrors(): bool
    {
        return $this->config->get('error_pages.enabled', true)
            && $this->config->get('app.env') !== 'production';
    }
}
