<?php

namespace Forge\Core\Contracts\Modules;

interface MarkDownInterface
{
    public function parse(string $markdown): string;

    public function parseFile(string $path): array;
}