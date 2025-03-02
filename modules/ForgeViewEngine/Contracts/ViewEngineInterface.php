<?php

namespace Forge\Modules\ForgeViewEngine\Contracts;

use Forge\Http\Response;

interface ViewEngineInterface
{
    /**
     * @param array<int,mixed> $data
     */
    public function render(string $view, array $data = []): Response;

    public function exists(string $view): bool;
}
