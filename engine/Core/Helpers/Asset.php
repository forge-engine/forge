<?php

namespace Forge\Core\Helpers;

class Asset
{
    /**
     * Include CSS files for modules by scanning the directory.
     *
     * @param array $modules Array of module names.
     * @return void
     */
    public static function includeModuleCss(array $modules): void
    {
        foreach ($modules as $module) {
            $cssDir = App::config()->get('app.config.public.modules') . '/' . $module . '/css';
            if (is_dir($cssDir)) {
                foreach (glob($cssDir . '/*.css') as $cssFile) {
                    $cssPath = str_replace(BASE_PATH, '', $cssFile);
                    echo '<link rel="stylesheet" href="' . Path::assetUrl('modules', $cssPath) . '">';
                }
            }
        }
    }

    /**
     * Include JS files for modules with defer attribute by scanning the directory.
     *
     * @param array $modules Array of module names.
     * @return void
     */
    public static function includeModuleJs(array $modules): void
    {
        foreach ($modules as $module) {
            $jsDir = App::config()->get('app.config.public.modules') . '/' . $module . '/js';
            if (is_dir($jsDir)) {
                foreach (glob($jsDir . '/*.js') as $jsFile) {
                    $jsPath = str_replace(BASE_PATH, '', $jsFile);
                    echo '<script src="' . Path::assetUrl('modules', $jsPath) . '" defer></script>';
                }
            }
        }
    }

    /**
     * Include CSS and JS files listed in the module's config file.
     *
     * @param array $modules Array of module names.
     * @return void
     */
    public static function includeModuleResources(array $modules): void
    {
        foreach ($modules as $module) {
            $config = App::config()->get("{$module}.resources");
            if ($config) {
                foreach ($config as $type => $files) {
                    foreach ($files as $file) {
                        $filePath = 'modules/' . $module . '/' . $file;
                        if ($type === 'css') {
                            echo '<link rel="stylesheet" href="' . Path::assetUrl('modules', $filePath) . '">';
                        } elseif ($type === 'js') {
                            echo '<script src="' . Path::assetUrl('modules', $filePath) . '" defer></script>';
                        }
                    }
                }
            }
        }
    }
}
