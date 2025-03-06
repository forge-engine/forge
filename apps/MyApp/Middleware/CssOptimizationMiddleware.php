<?php

namespace MyApp\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Http\Request;
use Forge\Http\Response;
use Closure;

// TODO: work in progress
class CssOptimizationMiddleware extends MiddlewareInterface
{
    /**
     * Process the incoming request and optimize CSS by removing unused classes.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getHeader('Content-Type') === 'text/html') {
            $html = $response->getContent();

            $usedClasses = $this->extractUsedClasses($html);

            $cssFiles = $this->extractCssFiles($html);

            $filteredCss = '';
            foreach ($cssFiles as $cssFile) {
                $cssContent = $this->fetchCssContent($cssFile, $request);
                if ($cssContent) {
                    $filteredCss .= $this->filterCss($cssContent, $usedClasses);
                }
            }

            $modifiedHtml = $this->removeCssLinks($html);
            $modifiedHtml = $this->injectFilteredCss($modifiedHtml, $filteredCss);

            $response->setContent($modifiedHtml);
        }

        return $response;
    }

    /**
     * Extract class names used in the HTML content.
     *
     * @param string $html
     * @return array
     */
    private function extractUsedClasses(string $html): array
    {
        $usedClasses = [];
        preg_match_all('/class=["\']([^"\']+)["\']/i', $html, $matches);
        foreach ($matches[1] as $classString) {
            $classes = explode(' ', trim($classString));
            foreach ($classes as $class) {
                if ($class !== '') {
                    $usedClasses[$class] = true;
                }
            }
        }
        return array_keys($usedClasses);
    }

    /**
     * Extract CSS file paths from <link> tags in the HTML.
     *
     * @param string $html
     * @return array
     */
    private function extractCssFiles(string $html): array
    {
        $cssFiles = [];
        preg_match_all('/<link\s+[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Fetch CSS content from the file path.
     *
     * @param string $cssFile
     * @param Request $request
     * @return string|null
     */
    private function fetchCssContent(string $cssFile, Request $request): ?string
    {
        $basePath = BASE_PATH . '/public';
        $filePath = realpath($basePath . $cssFile);

        if ($filePath && strpos($filePath, $basePath) === 0 && file_exists($filePath)) {
            return file_get_contents($filePath);
        }
        return null;
    }

    /**
     * Filter CSS to keep only rules matching used classes.
     *
     * @param string $cssContent
     * @param array $usedClasses
     * @return string
     */
    private function filterCss(string $cssContent, array $usedClasses): string
    {
        $filteredCss = '';

        $rules = explode('}', $cssContent);
        foreach ($rules as $rule) {
            $rule = trim($rule);
            if (empty($rule)) {
                continue;
            }

            $parts = explode('{', $rule, 2);
            if (count($parts) < 2) {
                continue;
            }
            $selector = trim($parts[0]);
            $styles = trim($parts[1]);

            if (preg_match('/^\.([a-zA-Z0-9_-]+)$/', $selector, $matches)) {
                $class = $matches[1];
                if (in_array($class, $usedClasses)) {
                    $filteredCss .= $selector . '{' . $styles . '}' . "\n";
                }
            }
        }
        return $filteredCss;
    }

    /**
     * Remove CSS <link> tags from the HTML.
     *
     * @param string $html
     * @return string
     */
    private function removeCssLinks(string $html): string
    {
        return preg_replace('/<link\s+[^>]*rel=["\']stylesheet["\'][^>]*>/i', '', $html);
    }

    /**
     * Inject filtered CSS into the HTML head.
     *
     * @param string $html
     * @param string $filteredCss
     * @return string
     */
    private function injectFilteredCss(string $html, string $filteredCss): string
    {
        if (empty($filteredCss)) {
            return $html;
        }
        $styleTag = "<style>\n" . $filteredCss . "\n</style>";
        if (stripos($html, '</head>') !== false) {
            return str_ireplace('</head>', $styleTag . '</head>', $html);
        }
        return $html . $styleTag;
    }

    private function extractClassesFromSelector(string $selector): array
    {
        $classes = [];
        preg_match_all('/\.([a-zA-Z0-9_-]+)/', $selector, $matches);
        if (isset($matches[1])) {
            $classes = $matches[1];
        }
        return $classes;
    }
}