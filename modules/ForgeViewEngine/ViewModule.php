<?php

namespace Forge\Modules\ForgeViewEngine;

use Forge\Core\Contracts\Modules\ModulesInterface;
use Forge\Core\DependencyInjection\Container;
use Forge\Core\Configuration\Config;
use Forge\Core\Contracts\Modules\ViewEngineInterface;
use Forge\Core\Helpers\Debug;

class ViewModule extends ModulesInterface
{
    public function register(Container $container): void
    {
        $config = $container->get(Config::class)->get('view');
        $engine = new PhpViewEngine(
            $config,
            BASE_PATH
        );

        $container->instance(ViewEngineInterface::class, $engine);
    }

    public function onAfterConfigLoaded(Container $container): void
    {
        $config = $container->get(Config::class);
        $existingPaths = $config->get('view.paths', []);
        $newPaths = [
            'apps/*/views',
            'apps/*/resources/views',
            'modules/*/views'
        ];

        $mergePaths = array_unique(array_merge($existingPaths, $newPaths));
        $config->set('view.paths', $mergePaths);
    }

}
