<?php

namespace Forge\Core\Contracts\Modules;

use Forge\Http\Request;
use Forge\Http\Response;
use Throwable;

interface ErrorHandlerInterface
{
    public function handle(Throwable $e, Request $request): Response;
}
