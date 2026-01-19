<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Middlewares;

use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Session\SessionInterface;
use Forge\Traits\WireHelper;

final class ForgeWireCleanMiddleware extends Middleware
{
  use WireHelper;

    public function __construct(
        private SessionInterface $session
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        $content = $response->getContent();
        $componentIdsInResponse = $this->trackComponentsInResponse($content);
        $this->cleanupStaleComponents($componentIdsInResponse);

        return $response;
    }
}
