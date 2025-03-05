<?php

namespace Forge\Modules\ForgeAuth;

use Forge\Http\Session;
use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;

class ForgeAuthModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        $container->instance(AuthService::class, function () use ($container) {
            return new AuthService(
                database: $container->get(DatabaseInterface::class),
                session: $container->get(Session::class),
                config: ['password_cost' => 12]
            );
        });
    }
}