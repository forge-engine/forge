<?php

namespace Forge\Modules\ForgeViewEngine\Compiler\Directive;

use Forge\Modules\ForgeViewEngine\Contracts\DirectiveInterface;

class ExtendsDirective implements DirectiveInterface
{
    public function handle(string $content): string
    {
        return preg_replace_callback(
            '/@extends\([\'"](.+?)[\'"]\)/',
            function ($matches) {
                return "<?php \$__parent = '{$matches[1]}'; ?>";
            },
            $content
        );
    }
}
