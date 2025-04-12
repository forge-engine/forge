<?php

namespace Forge\Modules\TestModule;

use Forge\Modules\TestModule\Contracts\TestInterface;

class TestModule
{
    public function register(Container $container): void
    {
        $container->instance(TestInterface::class, Test::class);
    }

}
