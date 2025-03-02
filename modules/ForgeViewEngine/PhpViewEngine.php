<?php

namespace Forge\Modules\ForgeViewEngine;

use Forge\Http\Response;
use Forge\Modules\ForgeViewEngine\Compiler\Compiler;
use Forge\Core\Contracts\Modules\ViewEngineInterface;
use Forge\Core\Helpers\App;

class PhpViewEngine implements ViewEngineInterface
{
    private ViewFinder $finder;
    private Compiler $compiler;
    private string $compiledPath;

    /**
     * @param array<int,mixed> $config
     */
    public function __construct(
        private array  $config,
        private string $basePath
    )
    {
        $this->finder = new ViewFinder(
            $this->config['paths'] ?? [],
            $this->basePath
        );

        $this->compiler = new Compiler();
        $this->compiledPath = $basePath . '/' . $config['cache'];
    }

    public function render(string $view, array $data = [], ?string $layout = null, bool $render_as_string = false): Response
    {
        $path = $this->finder->find($view);

        extract($data);

        ob_start();
        include $path;
        $viewContent = ob_get_clean();

        if ($layout) {
            $layoutPath = App::config()->get("app.paths.resources.layouts") . '/' . $layout . '.php';
            $layoutPath = BASE_PATH . '/' . $layoutPath;
            if (file_exists($layoutPath)) {
                ob_start();
                include $layoutPath;
                $layoutContent = ob_get_clean();
                $viewContent = str_replace('{{content}}', $viewContent, $layoutContent);
            } else {
                trigger_error("Layout '{$layout}' not found.", E_USER_WARNING);
            }
        }

        if ($render_as_string) {
            return (new Response())->html($viewContent);
        } else {
            //echo $viewContent;
            $response = new Response();
            $response->setHeader('Content-Type', 'text/html');
            return $response->html($viewContent);
        }
    }

    public function exists(string $view): bool
    {
        return $this->finder->find($view) !== null;
    }

    private function compile(string $path): string
    {
        $hash = md5_file($path);
        $compiled = "{$this->compiledPath}/{$hash}.php";

        if (!file_exists($compiled)) {
            $content = file_get_contents($path);
            $compiledContent = $this->compiler->compile($content);
            file_put_contents($compiled, $compiledContent);
        }

        return $compiled;
    }
}
