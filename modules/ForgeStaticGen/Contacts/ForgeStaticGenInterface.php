<?php

namespace Forge\Modules\ForgeStaticGen\Contacts;

interface ForgeStaticGenInterface
{
    public function build(string $contentDir): void;
}