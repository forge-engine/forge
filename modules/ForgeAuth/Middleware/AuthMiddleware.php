<?php

namespace Forge\Modules\ForgeAuth\Middleware;

use Forge\Core\Contracts\Http\Middleware\MiddlewareInterface;
use Forge\Core\Helpers\Redirect;
use Forge\Http\Request;
use Forge\Http\Response;
use Closure;

class AuthMiddleware extends MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('user_id')) {
            return Redirect::to('/login');
        }

        return $next($request);
    }
}