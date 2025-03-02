<?php

namespace Forge\Modules\ForgeRouter;

use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\Contracts\Modules\RouterInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Module\ModuleManifest;

class RouterModule extends ModulesInterface
{
    private ModuleManifest $manifest;

    public function __construct(ModuleManifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function register(Container $container): void
    {
        $container->instance(RouterInterface::class, new BasicRouter($container));
        $container->instance(ModuleManifest::class, $this->manifest);
    }

    public function onAfterConfigLoaded(Container $container): void
    {
    }
}
