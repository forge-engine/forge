<?php

namespace App\Modules\ForgeLogger\Contracts;

interface ForgeLoggerInterface
{
    public function registerDriver(string $name, LogDriverInterface $driver): void;
    public function log(string $message, string $level = 'INFO'): void;
}
