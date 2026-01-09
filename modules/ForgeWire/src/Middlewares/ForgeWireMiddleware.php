<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Middlewares;

use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\View\View;

final class ForgeWireMiddleware extends Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if ($request->hasHeader('X-ForgeWire')) {
            View::suppressLayout(true);
        }

        return $next($request);
    }
}
