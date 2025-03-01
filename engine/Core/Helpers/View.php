<?php

namespace Forge\Core\Helpers;

class View
{
    /**
     * Include a component view.
     *
     * @param string $appName
     * @param string $componentName
     * @param array $data
     *
     * @return void
     */
    public static function component(string $appName, string $componentName, array $data = []): void
    {
        $componentViewPath = App::config()->get('view.paths.components');
        $componentPath = BASE_PATH . '/' . $componentViewPath . '/' . $componentName . '.php';
        if (file_exists($componentPath)) {
            extract($data);
            include $componentPath;
        } else {
            trigger_error("Component '{$componentName}' not found.", E_USER_WARNING);
        }
    }

    public static function renderDebugBar(string|null $path, mixed $data): void
    {
        if (class_exists('\Forge\Modules\ForgeDebugBar\Collectors\ViewCollector')) {
            \Forge\Modules\ForgeDebugBar\Collectors\ViewCollector::instance()->addView($path, $data);
        }
    }
}