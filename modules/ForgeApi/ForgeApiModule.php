<?php

namespace Forge\Modules\ForgeApi;

use Forge\Core\Contracts\Modules\RouterInterface;
use Forge\Modules\ForgeApi\Contracts\ForgeApiInterface;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Helpers\Debug;

class ForgeApiModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        $container->bind(RouterInterface::class, ApiRouter::class);
        $container->bind(RateLimiter::class, RateLimiter::class);

        $container->instance(RateLimiter::class, function () {
            return new RateLimiter('api.rate_limit');
        });
    }
}