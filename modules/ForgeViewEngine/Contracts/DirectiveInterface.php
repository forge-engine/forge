<?php

namespace Forge\Modules\ForgeViewEngine\Contracts;

interface DirectiveInterface
{
    public function handle(string $content): string;
}
