<?php
declare(strict_types=1);

namespace Forge\Core\Http;

use Forge\Core\Routing\Router;
use Forge\Core\DI\Container;

final class Kernel
{
    public function __construct(
        private Router    $router,
        private Container $container
    )
    {
    }

    public function handler(Request $request): Response
    {
        try {
            $content = $this->router->dispatch($request);
            return new Response($content);
        } catch (\Throwable $exception) {
            return new Response("Error: " . $exception->getMessage(), 500);
        }
    }
}