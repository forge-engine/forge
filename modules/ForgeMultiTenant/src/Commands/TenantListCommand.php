<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Commands;

use App\Modules\ForgeMultiTenant\DTO\Tenant;
use App\Modules\ForgeMultiTenant\Services\TenantManager;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: 'tenant:list', description: 'List all configured tenants')]
final class TenantListCommand extends Command
{
    use OutputHelper;

    public function __construct(private readonly TenantManager $manager) {}

    public function execute(array $args): int
    {
        $tenants = $this->manager->all();
        if (!$tenants) {
            $this->warning('No tenants configured.');
            return 0;
        }

        $rows = array_map(fn(Tenant $t) => [
            'ID'        => $t->id,
            'Domain'    => $t->domain,
            'Sub-Domain'=> $t->subdomain ?? '-',
            'Strategy'  => $t->strategy->value,
        ], $this->manager->all());

        $this->table(['ID', 'Domain', 'Sub-Domain', 'Strategy'], $rows);
        return 0;
    }
}