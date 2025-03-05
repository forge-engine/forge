<?php

namespace Forge\Modules\ForgeViewEngine;

use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Configuration\Config;
use Forge\Core\Contracts\Modules\ViewEngineInterface;

class ViewModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        $config = $container->get(Config::class)->get('forge_view_engine');
        $appPaths = $container->get(Config::class)->get('app.paths.resources');
        $viewEngineConfig = array_merge($config, ['paths' => $appPaths]);
        $engine = new PhpViewEngine($viewEngineConfig);


        $container->instance(ViewEngineInterface::class, $engine);
    }

    public function onAfterConfigLoaded(Container $container): void
    {
        $config = $container->get(Config::class);
        $existingPaths = $config->get('app.paths.resources', []);
        $newPaths = [
            'modules/*/views'
        ];

        $mergePaths = array_unique(array_merge($existingPaths, $newPaths));
        $config->set('app.paths.resources', $mergePaths);
    }

}
