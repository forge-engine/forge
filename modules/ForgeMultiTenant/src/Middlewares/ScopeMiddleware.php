<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Middlewares;

use App\Modules\ForgeMultiTenant\Attributes\TenantScope;
use Forge\Traits\ResponseHelper;
use Forge\Core\Http\{Middleware, Request, Response};
use Forge\Core\Middleware\Attributes\RegisterMiddleware;

#[RegisterMiddleware(group: 'web', order: 2)]
final class ScopeMiddleware extends Middleware
{
    use ResponseHelper;
    /**
     * @throws \ReflectionException
     */
    public function handle(Request $request, callable $next): Response
    {
        $route   = $request->getAttribute('_route');
        $attr    = $this->extractScope($route);
        $tenant  = $request->getAttribute('tenant');

        $required = $attr?->value ?? 'both';

        if ($required === 'central' && $tenant !== null) {
            return $this->createErrorResponse($request, 'This route is only available on the central domain');
        }
        if ($required === 'tenant' && $tenant === null) {
            return $this->createErrorResponse($request, 'This route requires a tenant context');
        }

        return $next($request);
    }

    /**
     * @throws \ReflectionException
     */
    private function extractScope(array $route): ?object
    {
        [$class, $method] = $route['handler'];
        $ref = new \ReflectionMethod($class, $method);
        $attrs = $ref->getAttributes(TenantScope::class);
        if ($attrs) return $attrs[0]->newInstance();

        $attrs = (new \ReflectionClass($class))->getAttributes(TenantScope::class);
        return $attrs ? $attrs[0]->newInstance() : null;
    }
}