<?php

namespace Forge\Core\Helpers;


class Path
{
    /**
     * Get the base URL dynamically from the host.
     *
     * @return string
     */
    public static function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . $host;
    }

    /**
     * Generate a URL for an asset with cache busting.
     *
     * @param string $type The type of asset (e.g., 'css', 'js').
     * @param string $filePath The path to the asset file.
     * @return string
     */
    public static function assetUrl(string $type = 'asset', string $filePath = ''): string
    {
        $baseUrl = self::getBaseUrl();
        $version = $_ENV['APP_ENV'] === 'production' ? '?v=1.0.0' : '?v=' . time();
        return $baseUrl . '/' . trim($type . '/' . $filePath, '/') . $version;
    }

    /**
     * Generate URL for static site assets.
     *
     * This method is specifically for generating URLs to assets within the
     * statically generated site, where assets are typically located in the 'assets'
     * directory within the output directory (e.g., public/static/assets).
     *
     * @param string $path Asset path relative to the 'assets' directory in static output.
     * @return string
     */
    public static function staticAssetUrl(string $path): string
    {
        return '/assets/' . $path;
    }

    /**
     * Get the base path of the Base installation
     *
     * @param string $path Optionally, a path to append to the base path
     * @return string
     */
    public static function basePath(string $path = ''): string
    {
        return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get the path to the application "app" directory for the current application.
     *
     * @param string $path Optionally, a path to append to the base app path.
     * @return string
     */
    public static function appPath(string $path = ''): string
    {
        $appName = App::config()->get('app.name');
        $appName = $appName ?? 'MyApp';
        $fullPath = BASE_PATH . '/apps/' . $appName . ($path ? '/' . ltrim($path, '/') : '');
        return $fullPath;
    }

    /**
     * Get the path to a module's directory (or the base modules directory).
     *
     * @param string|null $moduleName Optional module name. If null, returns path to base modules directory.
     * @param string $path Optional path to append to the module's directory.
     * @return string
     */
    public static function modulePath(?string $moduleName = null, string $path = ''): string
    {
        $modulesDir = 'modules';

        if ($moduleName == null) {
            return self::basePath($modulesDir);
        } else {
            $modulePath = $modulesDir . '/' . ucfirst(trim($moduleName, '/'));
            return self::basePath($modulePath . ($path ? '/' . ltrim($path, '/') : ''));
        }
    }

    /**
     * Get the path to a content directory
     *
     * @param string $path
     * @return string
     */
    public static function contentPath(string $path = ''): string
    {
        return BASE_PATH . '/content/' . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Generate a URL to a module asset.
     *
     * @param string $moduleName
     * @param string $assetPath
     * @return string
     */
    public static function moduleAsset(string $moduleName, string $assetPath): string
    {
        return '/modules/' . $moduleName . '/' . $assetPath;
    }

    /**
     * Get a given file path.
     *
     * @param string $filePath
     * @param string $basePath
     * @return string
     */
    public static function filePath(string $filePath, ?string $basePath = BASE_PATH): string
    {
        if ($filePath !== null) {
            if (strpos($filePath, $basePath) === 0) {
                $filePath = substr($filePath, strlen($basePath));
            }
        }
        return $filePath;
    }

    /**
     * Get storage path.
     *
     * @param string $filePath
     * @param string $basePath
     * @return string
     */
    public static function storagePath(string $path = ''): string
    {
        $storageDir = self::basePath('storage/');
        return $storageDir . $path;
    }
}