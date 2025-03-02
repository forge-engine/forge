<?php

namespace Forge\Modules\ForgeViewEngine\Compiler\Directive;

use Forge\Modules\ForgeViewEngine\Contracts\DirectiveInterface;

class SectionDirective implements DirectiveInterface
{
    public function handle(string $content): string
    {
        $content = preg_replace_callback(
            '/@section\([\'"](.+?)[\'"])(?:,\s*[\'"](.+?)[\'"]\))?(.*?)(?:@endsection|$)/s',
            function ($matches) {
                var_dump($matches);
                if (isset($matches[2])) {
                    return "<?php \$__sections['{$matches[1]}'] = '{$matches[2]}'; ?>";
                } elseif (isset($matches[3])) {
                    return "<?php \$__sections['{$matches[1]}'] = function() { ?>"
                        . $matches[3]
                        . "<?php }; ?>";
                }
                return '';
            },
            $content
        );
        return $content ?? '';
    }
}
