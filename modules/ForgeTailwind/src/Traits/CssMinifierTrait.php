<?php
declare(strict_types=1);

namespace App\Modules\ForgeTailwind\Traits;

trait CssMinifierTrait
{
    /**
     * Minify CSS safely, preserving necessary whitespace and important rules.
     */
    private function minify(string $css): string
    {
        $css = str_replace(["\r\n", "\r"], "\n", $css);
        $css = preg_replace('/\/\*(?!\!)(.*?)\*\//s', '', $css);
        $css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $css);
        $css = preg_replace('/\s\s+/', ' ', $css);
        $css = str_replace(["\n", "\t"], '', $css);
        $css = preg_replace('/\s+!important/', '!important', $css);
        return trim($css);
    }
}