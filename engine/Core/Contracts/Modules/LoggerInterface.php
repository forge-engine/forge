<?php

namespace Forge\Core\Contracts\Modules;

interface LoggerInterface
{
    public function log(string $message): void;
}
