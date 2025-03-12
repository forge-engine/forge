<?php
declare(strict_types=1);

namespace Forge\Core\View;

use Forge\Core\Bootstrap;
use Forge\Core\DI\Container;

final class View
{
    private static ?string $layout = null;
    private static array $sections = [];
    private static string $currentSection = "";

    private static array $cache = [];

    public function __construct(
        private Container $container,
        private string $viewPath = BASE_PATH . "/app/views",
        private string $cachePath = BASE_PATH . "/storage/framework/views"
    ) {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function render(string $view, array $data = []): string
    {
        $viewContent = $this->compileView($view, $data);

        if (self::$layout) {
            $layoutData = array_merge($data, ["content" => $viewContent]);
            $viewContent = $this->compileLayout(self::$layout, $layoutData);
        }
        self::$layout = null;
        return $viewContent;
    }

    private function compileView(string $view, array $data): string
    {
        $viewFile = "{$this->viewPath}/{$view}.php";
        $cacheFile = "{$this->cachePath}/" . md5($view) . ".php";

        if ($this->shouldCompile($viewFile, $cacheFile)) {
            $content = $this->compile(file_get_contents($viewFile));
            file_put_contents($cacheFile, $content);
        }

        extract($data);
        ob_start();
        include $cacheFile;
        return ob_get_clean();
    }

    /**
     * Render a view directly without layout. For components.
     *
     * @param string $viewPath
     * @param array  $data
     * @return string
     */
    public function renderDirectly(string $viewPath, array $data = []): string
    {
        return $this->compileView($viewPath, $data);
    }

    private function compileLayout(string $layout, array $data): string
    {
        return $this->compileView("layouts/{$layout}", $data);
    }

    public function renderComponent(string $viewPath, array $data = []): string
    {
        return $this->compileComponent($viewPath, $data);
    }

    private function compileComponent(string $view, array $data): string
    {
        $viewFile = "{$this->viewPath}/components/{$view}.php";
        $cacheFile = "{$this->cachePath}/" . md5($view) . ".php";

        if ($this->shouldCompile($viewFile, $cacheFile)) {
            $content = $this->compile(file_get_contents($viewFile));
            file_put_contents($cacheFile, $content);
        }

        extract($data);

        include $cacheFile;
        return ob_get_clean();
    }

    private function shouldCompile(string $viewFile, string $cacheFile): bool
    {
        if (!Bootstrap::shouldCacheViews()) {
            return true;
        }
        return !file_exists($cacheFile) ||
            filemtime($viewFile) > filemtime($cacheFile);
    }

    private function compile(string $content): string
    {
        return $content;
    }

    public static function layout(string $layout): void
    {
        self::$layout = $layout;
    }

    public static function startSection(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        self::$sections[self::$currentSection] = ob_get_clean();
        self::$currentSection = "";
    }

    public static function section(string $name): string
    {
        return self::$sections[$name] ?? "";
    }

    /**
     * Render a component.
     *
     * @param string $name
     * @param array $props
     * @return string
     */
    public static function component(string $name, array $props = []): string
    {
        return Component::render($name, $props);
    }
}
