<?php
declare(strict_types=1);

namespace Forge\Core\Http;

use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Exceptions\InvalidMiddlewareResponse;

abstract class Middleware
{
    /**
     * @param Request $request
     * @param callable $next
     *
     * @return Response
     * @throws InvalidMiddlewareResponse
     */
    abstract public function handle(Request $request, callable $next): Response;
}
