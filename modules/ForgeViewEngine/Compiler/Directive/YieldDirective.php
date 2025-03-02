<?php

namespace Forge\Modules\ForgeViewEngine\Compiler\Directive;

use Forge\Modules\ForgeViewEngine\Contracts\DirectiveInterface;

class YieldDirective implements DirectiveInterface
{
    public function handle(string $content): string
    {
        return preg_replace_callback(
            '/@yield\([\'"](.+?)[\'"]\)/',
            function ($matches) {
                return "<?= \$__sections['{$matches[1]}'] ?? '' ?>";
            },
            $content
        );
    }
}
