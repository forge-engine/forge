<?php
declare(strict_types=1);

namespace Forge\Core\Contracts;

use Forge\Core\Http\Request;
use Forge\Core\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
