<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Middlewares;

use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Middleware\Attributes\RegisterMiddleware;
use App\Modules\ForgeMultiTenant\Services\TenantManager;

#[RegisterMiddleware(group: "web", order: 1, allowDuplicate: false, overrideClass: null, enabled: true)]
final class TenantMiddleware extends Middleware {

    public function __construct(private readonly TenantManager $tenantManager) {}

    public function handle(Request $request, callable $next): Response
    {
        $host = $request->getHeader('Host') ?? $request->serverParams['HTTP_HOST'] ?? null;
        $tenant = $this->tenantManager->resolveByDomain($host);


        if(!$tenant) {
            echo 'No tenant matched';
        } else {
            echo "Resolved tenant: {$tenant['id']} \n";
        }

        $request->setAttribute('tenant', $tenant);

        return $next($request);
    }
}