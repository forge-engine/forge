<?php
declare(strict_types=1);

namespace Forge\Core\View;

use Forge\Core\Bootstrap;
use Forge\Core\DI\Container;

final class View
{
    private static ?string $layout = null;
    private static array $sections = [];
    private static string $currentSection = '';

    private static array $cache = [];

    public function __construct(
        private Container $container,
        private string    $viewPath = BASE_PATH . '/app/views',
        private string    $cachePath = BASE_PATH . '/storage/framework/views'
    )
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function render(string $view, array $data = []): string
    {
        $viewContent = $this->compileView($view, $data);

        if (self::$layout) {
            $layoutContent = $this->compileLayout(self::$layout, $data);
            $viewContent = str_replace(
                '@content()',
                $viewContent,
                $layoutContent
            );
        }

        self::$layout = null;
        return $viewContent;
    }

    private function compileView(string $view, array $data): string
    {
        $viewFile = "{$this->viewPath}/{$view}.php";
        $cacheFile = "{$this->cachePath}/" . md5($view) . '.php';

        if ($this->shouldCompile($viewFile, $cacheFile)) {
            $content = $this->compile(file_get_contents($viewFile));
            file_put_contents($cacheFile, $content);
        }

        extract($data);
        ob_start();
        include $cacheFile;
        return ob_get_clean();
    }

    private function compileLayout(string $layout, array $data): string
    {
        return $this->compileView("layouts/{$layout}", $data);
    }

    private function shouldCompile(string $viewFile, string $cacheFile): bool
    {
        if (!Bootstrap::shouldCacheViews()) {
            return true;
        }
        return !file_exists($cacheFile) ||
            filemtime($viewFile) > filemtime($cacheFile);
    }

    private function renderDirectly(string $view, array $data): string
    {
        $viewFile = "{$this->viewPath}/{$view}.php";

        extract($data);
        ob_start();
        include $viewFile;
        return ob_get_clean();
    }

    private function compile(string $content): string
    {
        $content = preg_replace_callback('/\{\{(.*?)\}\}/', function ($matches) {
            return "<?= e({$matches[1]}) ?>";
        }, $content);

        $replacements = [
            '/@layout\(\'(.*?)\'\)/' => '<?php \Forge\Core\View\View::layout(\'$1\'); ?>',
            '/@section\(\'(.*?)\'\)/' => '<?php \Forge\Core\View\View::startSection(\'$1\'); ?>',
            '/@endsection/' => '<?php \Forge\Core\View\View::endSection(); ?>',
            '/@component\(\'(.*?)\'(?:,\s*(.*?))?\)/' => '<?php echo \Forge\Core\View\Component::render(\'$1\', $2 ?? []); ?>',
            '/@endcomponent/' => '<?php // End component ?>',
        ];

        $compiled = preg_replace(array_keys($replacements), array_values($replacements), $content);

        return $compiled;
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
        self::$currentSection = '';
    }

    public static function section(string $name): string
    {
        error_log("Section content for {$name}: " . (self::$sections[$name] ?? ''));
        return self::$sections[$name] ?? '';
    }
}