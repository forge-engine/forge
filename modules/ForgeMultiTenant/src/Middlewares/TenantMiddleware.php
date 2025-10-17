<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Middlewares;

use App\Modules\ForgeMultiTenant\Services\CentralDomain;
use App\Modules\ForgeMultiTenant\Services\TenantConnectionFactory;
use Forge\Core\Database\Connection;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Container;
use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Middleware\Attributes\RegisterMiddleware;
use App\Modules\ForgeMultiTenant\Services\TenantManager;
use Forge\Exceptions\MissingServiceException;
use Forge\Exceptions\ResolveParameterException;
use PDO;
use ReflectionException;

#[RegisterMiddleware(group: "web", order: 1, allowDuplicate: false, overrideClass: null, enabled: true)]
final class TenantMiddleware extends Middleware {

    public function __construct(private readonly TenantManager $tenantManager) {}

    /**
     * @throws ReflectionException
     * @throws MissingServiceException
     * @throws ResolveParameterException
     */
    public function handle(Request $request, callable $next): Response
    {
        $rawHost = $request->getHeader('Host') ?? $request->serverParams['HTTP_HOST'] ?? '';
        $host    = CentralDomain::stripPort($rawHost);

        $tenant = $this->tenantManager->resolveByDomain($host) ?? null;

        if ($tenant !== null) {
            $request->setAttribute('tenant', $tenant);

            $container = Container::getInstance();
            $newConn = $container->get(TenantConnectionFactory::class)->forTenant($tenant);

            $container->setInstance(Connection::class, $newConn);
            $container->setInstance(PDO::class, $newConn->getPdo());
            $container->setInstance(QueryBuilder::class, new QueryBuilder($newConn));
        }

        return $next($request);
    }
}