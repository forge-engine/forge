<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Services;

use Forge\Core\DI\Attributes\Service;

#[Service]
final class TenantManager  {
    private array $tenants = [
        'default' => ['id' => 'default', 'domain' => 'forge-v3.test', 'subdomain' => null],
        'my-tenant' => ['id' => 'my-tenant', 'domain' => 'forge-v3.test', 'subdomain' => 'my-tenant'],
    ];

    private ?array $current = null;

    public function resolveByDomain(string $host): ?array
    {
        foreach ($this->tenants as $tenant) {
            $fullHost = $tenant['subdomain'] ? "{$tenant['subdomain']}.{$tenant['domain']}" : $tenant['domain'];
            if ($host === $fullHost) {
                $this->current = $tenant;
                return $tenant;
            }
        }
        return null;
    }

    public function current(): ?array
    {
        return $this->current;
    }
}