<?php

namespace Forge\Http;

use Forge\Core\DependencyInjection\Container;
use Forge\Core\Bootstrap\AppManager;

class HttpKernel
{
    public function __construct(
        private Container $container,
        private AppManager $appManager
    ) {}

    public function handle(Request $request): Response
    {
        $this->appManager->trigger('beforeRequest', $this->container);

        foreach ($this->appManager->getApps() as $app) {
            $response = $app->handleRequest($request);
            if ($response !== null) {
                $this->appManager->trigger('afterRequest', $this->container);
                return $response;
            }
        }

        throw new \RuntimeException("No app handled the request.");
    }
}
