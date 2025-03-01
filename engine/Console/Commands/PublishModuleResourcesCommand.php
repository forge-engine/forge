<?php

namespace Forge\Console\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Helpers\App;
use Forge\Core\Helpers\Strings;
use Forge\Core\Traits\OutputHelper;

class PublishModuleResourcesCommand implements CommandInterface
{
    use OutputHelper;

    private const ALLOWED_TYPES = ['config', 'views', 'components', 'assets', 'all'];
    private const ASSET_TYPES = ['css', 'js', 'images', 'fonts'];

    public function getName(): string
    {
        return 'publish';
    }

    public function getDescription(): string
    {
        return 'Publish module resources to application directories';
    }

    public function execute(array $args): int
    {
        if (count($args) < 1) {
            $this->error('Module name required');
            $this->line('Usage: publish module-name [--type=config|views|components|assets|all]');
            return 1;
        }

        $moduleName = Strings::toPascalCase($args[0]);

        $type = $this->getPublishType($args);

        try {
            $this->validateModule($moduleName);
            $this->publishResources($moduleName, $type);
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function getPublishType(array $args): string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--type=')) {
                $type = strtolower(substr($arg, 7));
                if (in_array($type, self::ALLOWED_TYPES)) {
                    return $type;
                }
            }
        }
        return 'all';
    }

    private function validateModule(string $moduleName): void
    {
        $modulePath = BASE_PATH . "/modules/{$moduleName}";
        if (!is_dir($modulePath)) {
            throw new \RuntimeException("Module {$moduleName} not found");
        }
    }

    private function publishResources(string $moduleName, string $type): void
    {
        $this->info("Publishing resources for module: {$moduleName}");

        $publishMap = [
            'config' => fn() => $this->publishConfig($moduleName),
            'views' => fn() => $this->publishViews($moduleName),
            'components' => fn() => $this->publishComponents($moduleName),
            'assets' => fn() => $this->publishAssets($moduleName),
        ];

        if ($type === 'all') {
            foreach ($publishMap as $publish) $publish();
        } else {
            $publishMap[$type]();
        }

        $this->success('Publishing completed successfully');
    }

    private function publishConfig(string $moduleName): void
    {
        $source = BASE_PATH . "/modules/{$moduleName}/config";
        $dest = $this->getConfigDestination($moduleName);
        $this->copyDirectory($source, $dest, 'config');
    }

    private function publishViews(string $moduleName): void
    {
        $source = BASE_PATH . "/modules/{$moduleName}/resources/views";
        $dest = $this->getViewsDestination($moduleName);
        $this->copyDirectory($source, $dest, 'views');
    }

    private function publishComponents(string $moduleName): void
    {
        $source = BASE_PATH . "/modules/{$moduleName}/resources/components";
        $dest = $this->getComponentsDestination($moduleName);
        $this->copyDirectory($source, $dest, 'components');
    }

    private function publishAssets(string $moduleName): void
    {
        foreach (self::ASSET_TYPES as $assetType) {
            $source = BASE_PATH . "/modules/{$moduleName}/resources/assets/{$assetType}";
            $dest = $this->getAssetsDestination($moduleName, $assetType);

            if (is_dir($source)) {
                $this->copyDirectory($source, $dest, $assetType);
            }
        }
    }

    private function copyDirectory(string $source, string $dest, string $type): void
    {
        if (!is_dir($source)) {
            $this->comment("No {$type} resources found");
            return;
        }

        $this->createDestinationDir($dest);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $target = $dest . str_replace($source, '', $file->getPathname());

            if ($file->isDir()) {
                mkdir($target, 0755);
            } else {
                copy($file->getPathname(), $target);
                $this->line("Published: {$type}/" . basename($file));
            }
        }
    }

    private function createDestinationDir(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true)) {
            throw new \RuntimeException("Failed to create directory: {$path}");
        }
    }

    private function getConfigDestination(string $moduleName): string
    {
        $base = App::config()->get('app.config', 'apps/MyApp/config');

        return BASE_PATH . "/{$base}/modules/";
    }

    private function getViewsDestination(string $moduleName): string
    {
        $moduleNameToKebabCase = Strings::toKebabCase($moduleName);
        $viewsPath = App::config()->get('app.paths.resources.views', 'apps/MyApp/resources/views');
        return BASE_PATH . "/{$viewsPath}/modules/{$moduleNameToKebabCase}/";
    }

    private function getComponentsDestination(string $moduleName): string
    {
        $moduleNameToKebabCase = Strings::toKebabCase($moduleName);
        $componentsPath = App::config()->get('app.paths.resources.components', 'apps/MyApp/resources/components');
        return BASE_PATH . "/{$componentsPath}/modules/{$moduleNameToKebabCase}/";
    }

    private function getAssetsDestination(string $moduleName, string $assetType): string
    {
        $moduleNameToKebabCase = Strings::toKebabCase($moduleName);
        $publicPath = App::config()->get('app.paths.public.modules', 'public/modules');
        return BASE_PATH . "/{$publicPath}/{$moduleNameToKebabCase}/{$assetType}/";
    }
}