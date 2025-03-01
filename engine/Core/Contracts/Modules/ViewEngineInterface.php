<?php

namespace Forge\Core\Contracts\Modules;

use Forge\Http\Response;

interface ViewEngineInterface
{
    /**
     * @param array<int,mixed> $data
     */
    public function render(string $view, array $data = [], ?string $layout = null, bool $render_as_string = false): Response;

    public function exists(string $view): bool;
}
